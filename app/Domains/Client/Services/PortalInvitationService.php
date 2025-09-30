<?php

namespace App\Domains\Client\Services;

use App\Domains\Email\Services\UnifiedMailService;
use App\Models\CommunicationLog;
use App\Models\Contact;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Portal Invitation Service
 *
 * Handles the complete lifecycle of client portal invitations including:
 * - Token generation and management
 * - Email sending with rate limiting
 * - Invitation validation and acceptance
 * - Expiration and resend logic
 * - Security and audit logging
 */
class PortalInvitationService
{
    /**
     * Configuration for invitations
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('portal.invitations', [
            'enabled' => true,
            'expiration_hours' => 72,
            'max_per_contact_per_day' => 5,
            'max_per_client_per_day' => 20,
            'require_email_verification' => true,
            'auto_login_after_acceptance' => true,
            'password_requirements' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_special' => false,
            ],
        ]);
    }

    /**
     * Generate and send invitation to a contact
     */
    public function sendInvitation(Contact $contact, User $sentBy): array
    {
        try {
            // Check if invitations are enabled
            if (! $this->config['enabled']) {
                return $this->errorResponse('Portal invitations are currently disabled');
            }

            // Validate contact can receive invitation
            $validation = $this->validateContactForInvitation($contact);
            if (! $validation['valid']) {
                return $this->errorResponse($validation['message']);
            }

            // Check rate limits
            if (! $this->checkRateLimits($contact)) {
                return $this->errorResponse('Rate limit exceeded. Please try again later.');
            }

            // Generate unique token
            $token = $this->generateInvitationToken();
            $expiresAt = now()->addHours($this->config['expiration_hours']);

            // Update contact with invitation details
            $contact->update([
                'invitation_token' => Hash::make($token),
                'invitation_sent_at' => now(),
                'invitation_expires_at' => $expiresAt,
                'invitation_sent_by' => $sentBy->id,
                'invitation_status' => 'sent',
                'has_portal_access' => true,
            ]);

            // Send invitation email
            $this->sendInvitationEmail($contact, $token);

            // Log activity
            activity()
                ->causedBy($sentBy)
                ->performedOn($contact)
                ->withProperties([
                    'action' => 'invitation_sent',
                    'expires_at' => $expiresAt,
                    'client_id' => $contact->client_id,
                ])
                ->log('Portal invitation sent to contact');

            // Log to communication log
            $this->logCommunication($contact, $sentBy, 'invitation_sent',
                "Portal invitation sent to {$contact->name}",
                "Invitation email sent to {$contact->email}. The invitation will expire on {$expiresAt->format('F j, Y \a\t g:i A')}. The contact can use this invitation to set up their own password for portal access."
            );

            // Update rate limiter
            $this->updateRateLimiter($contact);

            return $this->successResponse('Invitation sent successfully', [
                'expires_at' => $expiresAt,
                'contact_id' => $contact->id,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send portal invitation', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to send invitation. Please try again.');
        }
    }

    /**
     * Resend an existing invitation
     */
    public function resendInvitation(Contact $contact, ?User $sentBy = null): array
    {
        // Check if contact has a pending invitation
        if ($contact->invitation_status !== 'sent' && $contact->invitation_status !== 'expired') {
            return $this->errorResponse('No pending invitation to resend');
        }

        $user = $sentBy ?? auth()->user();

        // Log the resend action to communication log
        $this->logCommunication($contact, $user, 'invitation_resent',
            "Portal invitation resent to {$contact->name}",
            "A new portal invitation was sent to {$contact->email} replacing the previous invitation."
        );

        // Generate new token and reset expiration
        return $this->sendInvitation($contact, $user);
    }

    /**
     * Validate invitation token
     */
    public function validateToken(string $token): ?Contact
    {
        // Find all contacts with pending invitations
        $contacts = Contact::where('invitation_status', 'sent')
            ->where('invitation_expires_at', '>', now())
            ->get();

        foreach ($contacts as $contact) {
            if (Hash::check($token, $contact->invitation_token)) {
                return $contact;
            }
        }

        return null;
    }

    /**
     * Accept invitation and set password
     */
    public function acceptInvitation(string $token, string $password): array
    {
        try {
            // Validate token
            $contact = $this->validateToken($token);
            if (! $contact) {
                return $this->errorResponse('Invalid or expired invitation token');
            }

            // Validate password requirements
            $passwordValidation = $this->validatePassword($password);
            if (! $passwordValidation['valid']) {
                return $this->errorResponse($passwordValidation['message'], 'INVALID_PASSWORD', $passwordValidation['errors']);
            }

            // Set password and mark invitation as accepted
            $contact->update([
                'password_hash' => Hash::make($password),
                'password_changed_at' => now(),
                'invitation_accepted_at' => now(),
                'invitation_status' => 'accepted',
                'invitation_token' => null,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]);

            // Reset any failed login attempts
            $contact->resetFailedLoginAttempts();

            // Log activity
            activity()
                ->performedOn($contact)
                ->withProperties([
                    'action' => 'invitation_accepted',
                    'client_id' => $contact->client_id,
                ])
                ->log('Portal invitation accepted');

            // Log to communication log
            $this->logCommunication($contact, null, 'invitation_accepted',
                "Portal invitation accepted by {$contact->name}",
                'The contact successfully accepted their portal invitation and set up their password. They now have active access to the client portal.'
            );

            return $this->successResponse('Invitation accepted successfully', [
                'contact' => $contact,
                'auto_login' => $this->config['auto_login_after_acceptance'],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to accept invitation', [
                'token' => substr($token, 0, 8).'...',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to accept invitation. Please try again.');
        }
    }

    /**
     * Revoke an invitation
     */
    public function revokeInvitation(Contact $contact, User $revokedBy): array
    {
        if (! in_array($contact->invitation_status, ['sent', 'pending'])) {
            return $this->errorResponse('No active invitation to revoke');
        }

        $contact->update([
            'invitation_token' => null,
            'invitation_status' => 'revoked',
            'has_portal_access' => false,
        ]);

        // Log activity
        activity()
            ->causedBy($revokedBy)
            ->performedOn($contact)
            ->withProperties([
                'action' => 'invitation_revoked',
                'client_id' => $contact->client_id,
            ])
            ->log('Portal invitation revoked');

        // Log to communication log
        $this->logCommunication($contact, $revokedBy, 'invitation_revoked',
            "Portal invitation revoked for {$contact->name}",
            "The pending portal invitation was revoked by {$revokedBy->name}. The contact will no longer be able to use the invitation link."
        );

        return $this->successResponse('Invitation revoked successfully');
    }

    /**
     * Check and update expired invitations
     */
    public function updateExpiredInvitations(): int
    {
        $expired = Contact::where('invitation_status', 'sent')
            ->where('invitation_expires_at', '<', now())
            ->update(['invitation_status' => 'expired']);

        if ($expired > 0) {
            Log::info("Updated {$expired} expired portal invitations");
        }

        return $expired;
    }

    /**
     * Get invitation statistics for a client
     */
    public function getInvitationStats($client): array
    {
        $stats = Contact::where('client_id', $client->id)
            ->selectRaw('invitation_status, COUNT(*) as count')
            ->whereNotNull('invitation_status')
            ->groupBy('invitation_status')
            ->pluck('count', 'invitation_status')
            ->toArray();

        return [
            'total' => array_sum($stats),
            'sent' => $stats['sent'] ?? 0,
            'accepted' => $stats['accepted'] ?? 0,
            'expired' => $stats['expired'] ?? 0,
            'revoked' => $stats['revoked'] ?? 0,
            'acceptance_rate' => $this->calculateAcceptanceRate($stats),
        ];
    }

    /**
     * Validate contact for invitation
     */
    protected function validateContactForInvitation(Contact $contact): array
    {
        // Check if contact has email
        if (! $contact->email) {
            return ['valid' => false, 'message' => 'Contact must have an email address'];
        }

        // Check if already has accepted invitation
        if ($contact->invitation_status === 'accepted') {
            return ['valid' => false, 'message' => 'Contact has already accepted an invitation'];
        }

        // Check if client is active
        if (! $contact->client || $contact->client->status !== 'active') {
            return ['valid' => false, 'message' => 'Client must be active to send invitations'];
        }

        // Check if contact already has password set
        if ($contact->password_hash && ! $contact->invitation_token) {
            return ['valid' => false, 'message' => 'Contact already has portal access configured'];
        }

        return ['valid' => true];
    }

    /**
     * Check rate limits
     */
    protected function checkRateLimits(Contact $contact): bool
    {
        $contactKey = "portal_invitation:contact:{$contact->id}";
        $clientKey = "portal_invitation:client:{$contact->client_id}";

        // Check per-contact limit
        if (RateLimiter::tooManyAttempts($contactKey, $this->config['max_per_contact_per_day'])) {
            return false;
        }

        // Check per-client limit
        if (RateLimiter::tooManyAttempts($clientKey, $this->config['max_per_client_per_day'])) {
            return false;
        }

        return true;
    }

    /**
     * Update rate limiter after sending invitation
     */
    protected function updateRateLimiter(Contact $contact): void
    {
        $contactKey = "portal_invitation:contact:{$contact->id}";
        $clientKey = "portal_invitation:client:{$contact->client_id}";

        RateLimiter::hit($contactKey, 86400); // 24 hours
        RateLimiter::hit($clientKey, 86400); // 24 hours
    }

    /**
     * Generate unique invitation token
     */
    protected function generateInvitationToken(): string
    {
        do {
            $token = Str::random(40);
            $hash = Hash::make($token);
            $exists = Contact::where('invitation_token', $hash)->exists();
        } while ($exists);

        return $token;
    }

    /**
     * Send invitation email
     */
    protected function sendInvitationEmail(Contact $contact, string $token): void
    {
        $invitationUrl = route('client.invitation.show', ['token' => $token]);

        // Use unified mail service
        $mailService = app(UnifiedMailService::class);

        // Prepare email content
        $emailBody = view('emails.portal-invitation', [
            'contact' => $contact,
            'contactName' => $contact->name,
            'clientName' => $contact->client->name,
            'companyName' => $contact->client->company->name ?? 'Nestogy',
            'invitationUrl' => $invitationUrl,
            'expiresAt' => $contact->invitation_expires_at,
            'expiresInHours' => now()->diffInHours($contact->invitation_expires_at),
        ])->render();

        // Send the email immediately (portal invitations are time-sensitive)
        $mailService->sendNow([
            'company_id' => $contact->company_id,
            'client_id' => $contact->client_id,
            'contact_id' => $contact->id,
            'to_email' => $contact->email,
            'to_name' => $contact->name,
            'subject' => "You're invited to access your ".($contact->client->company->name ?? 'Nestogy').' Client Portal',
            'html_body' => $emailBody,
            'category' => 'portal',
            'priority' => 'critical',
            'related_type' => Contact::class,
            'related_id' => $contact->id,
            'metadata' => [
                'invitation_token' => substr($token, 0, 8).'...',
                'expires_at' => $contact->invitation_expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Validate password against requirements
     */
    protected function validatePassword(string $password): array
    {
        $errors = [];
        $config = $this->config['password_requirements'];

        if (strlen($password) < $config['min_length']) {
            $errors[] = "Password must be at least {$config['min_length']} characters";
        }

        if ($config['require_uppercase'] && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if ($config['require_lowercase'] && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if ($config['require_numbers'] && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($config['require_special'] && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => implode('. ', $errors),
        ];
    }

    /**
     * Calculate acceptance rate
     */
    protected function calculateAcceptanceRate(array $stats): float
    {
        $total = ($stats['accepted'] ?? 0) + ($stats['expired'] ?? 0) + ($stats['revoked'] ?? 0);

        if ($total === 0) {
            return 0.0;
        }

        return round(($stats['accepted'] ?? 0) / $total * 100, 2);
    }

    /**
     * Return success response
     */
    protected function successResponse(string $message, array $data = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message, string $code = 'ERROR', array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'errors' => $errors,
        ];
    }

    /**
     * Log portal invitation events to communication log
     */
    protected function logCommunication(Contact $contact, ?User $user, string $action, string $subject, string $notes): void
    {
        try {
            // Use portal_invitation type for all invitation-related communications
            $type = 'portal_invitation';

            CommunicationLog::create([
                'client_id' => $contact->client_id,
                'user_id' => $user ? $user->id : null,
                'contact_id' => $contact->id,
                'type' => $type,
                'channel' => 'email',
                'contact_name' => $contact->name,
                'contact_email' => $contact->email,
                'contact_phone' => $contact->phone,
                'subject' => $subject,
                'notes' => $notes,
                'follow_up_required' => false,
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the invitation process
            Log::error('Failed to log invitation to communication log', [
                'contact_id' => $contact->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
