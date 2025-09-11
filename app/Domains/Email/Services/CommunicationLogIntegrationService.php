<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailMessage;
use App\Models\Client;
use App\Models\CommunicationLog;
use Illuminate\Support\Facades\Log;

class CommunicationLogIntegrationService
{
    public function createCommunicationLogFromEmail(EmailMessage $emailMessage): ?CommunicationLog
    {
        try {
            // Skip if already logged
            if ($emailMessage->is_communication_logged) {
                return null;
            }

            // Try to find the client based on email addresses
            $client = $this->findClientFromEmailAddresses($emailMessage);
            
            if (!$client) {
                // No client found, don't create log entry
                return null;
            }

            // Determine communication type and direction
            $type = 'email';
            $direction = $this->determineDirection($emailMessage, $client);
            $channel = 'email';

            // Create communication log entry
            $communicationLog = CommunicationLog::create([
                'client_id' => $client->id,
                'user_id' => $emailMessage->emailAccount->user_id,
                'type' => $type,
                'direction' => $direction,
                'channel' => $channel,
                'subject' => $emailMessage->subject ?: 'No Subject',
                'summary' => $this->generateSummary($emailMessage),
                'details' => [
                    'from' => $emailMessage->from_address,
                    'from_name' => $emailMessage->from_name,
                    'to' => $emailMessage->to_addresses,
                    'cc' => $emailMessage->cc_addresses,
                    'bcc' => $emailMessage->bcc_addresses,
                    'message_id' => $emailMessage->message_id,
                    'email_message_id' => $emailMessage->id,
                    'has_attachments' => $emailMessage->has_attachments,
                    'attachment_count' => $emailMessage->attachments->count(),
                ],
                'outcome' => $this->determineOutcome($emailMessage),
                'contact_person' => $this->determineContactPerson($emailMessage, $client),
                'follow_up_required' => false, // Default to false for emails
                'follow_up_date' => null,
                'communication_date' => $emailMessage->sent_at ?: $emailMessage->received_at,
                'duration_minutes' => null, // Emails don't have duration
                'notes' => $this->extractNotes($emailMessage),
                'auto_generated' => true,
                'source_type' => 'email',
                'source_id' => $emailMessage->id,
            ]);

            // Update email message to mark as logged
            $emailMessage->update([
                'is_communication_logged' => true,
                'communication_log_id' => $communicationLog->id,
            ]);

            Log::info('Communication log created from email', [
                'email_message_id' => $emailMessage->id,
                'communication_log_id' => $communicationLog->id,
                'client_id' => $client->id,
            ]);

            return $communicationLog;

        } catch (\Exception $e) {
            Log::error('Failed to create communication log from email', [
                'email_message_id' => $emailMessage->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function findClientFromEmailAddresses(EmailMessage $emailMessage): ?Client
    {
        $allEmails = array_merge(
            [$emailMessage->from_address],
            $emailMessage->to_addresses ?: [],
            $emailMessage->cc_addresses ?: [],
            $emailMessage->bcc_addresses ?: []
        );

        // Remove the email account owner's email
        $accountEmail = $emailMessage->emailAccount->email_address;
        $allEmails = array_filter($allEmails, function($email) use ($accountEmail) {
            return strtolower($email) !== strtolower($accountEmail);
        });

        // Try to find client by primary email
        foreach ($allEmails as $email) {
            $client = Client::where('email', $email)->first();
            if ($client) {
                return $client;
            }
        }

        // Try to find client by contact emails (if stored in JSON field)
        foreach ($allEmails as $email) {
            $client = Client::whereJsonContains('contact_emails', $email)->first();
            if ($client) {
                return $client;
            }
        }

        // Try to find client through contacts
        foreach ($allEmails as $email) {
            $contact = \App\Models\Contact::where('email', $email)->first();
            if ($contact && $contact->client) {
                return $contact->client;
            }
        }

        return null;
    }

    private function determineDirection(EmailMessage $emailMessage, Client $client): string
    {
        $accountEmail = strtolower($emailMessage->emailAccount->email_address);
        $fromEmail = strtolower($emailMessage->from_address);

        // If the email is from our account, it's outbound
        if ($fromEmail === $accountEmail) {
            return 'outbound';
        }

        // Otherwise, it's inbound
        return 'inbound';
    }

    private function generateSummary(EmailMessage $emailMessage): string
    {
        $subject = $emailMessage->subject ?: 'No Subject';
        $preview = $emailMessage->preview ?: '';
        
        if ($preview) {
            return $subject . ' - ' . \Illuminate\Support\Str::limit($preview, 100);
        }
        
        return $subject;
    }

    private function determineOutcome(EmailMessage $emailMessage): string
    {
        // For emails, we can determine outcome based on folder or status
        $folderType = $emailMessage->emailFolder->type;
        
        return match($folderType) {
            'sent' => 'completed',
            'drafts' => 'pending',
            'trash' => 'cancelled',
            default => 'completed' // Assume received emails are completed communications
        };
    }

    private function determineContactPerson(EmailMessage $emailMessage, Client $client): ?string
    {
        $direction = $this->determineDirection($emailMessage, $client);
        
        if ($direction === 'inbound') {
            // For inbound emails, the contact person is the sender
            return $emailMessage->from_name ?: $emailMessage->from_address;
        } else {
            // For outbound emails, try to find the primary recipient from client contacts
            if ($emailMessage->to_addresses && !empty($emailMessage->to_addresses)) {
                $primaryRecipient = $emailMessage->to_addresses[0];
                
                // Try to find contact name
                $contact = \App\Models\Contact::where('client_id', $client->id)
                    ->where('email', $primaryRecipient)
                    ->first();
                
                return $contact ? $contact->name : $primaryRecipient;
            }
        }
        
        return null;
    }

    private function extractNotes(EmailMessage $emailMessage): ?string
    {
        // Extract first few lines of email as notes
        $body = $emailMessage->body_text ?: strip_tags($emailMessage->body_html ?: '');
        
        if (empty($body)) {
            return null;
        }
        
        // Take first 500 characters
        $notes = \Illuminate\Support\Str::limit($body, 500);
        
        // Clean up the notes
        $notes = preg_replace('/\s+/', ' ', $notes); // Normalize whitespace
        $notes = trim($notes);
        
        return $notes ?: null;
    }

    public function shouldCreateCommunicationLog(EmailMessage $emailMessage): bool
    {
        // Don't log if already logged
        if ($emailMessage->is_communication_logged) {
            return false;
        }

        // Don't log drafts
        if ($emailMessage->is_draft) {
            return false;
        }

        // Don't log deleted messages
        if ($emailMessage->is_deleted) {
            return false;
        }

        // Only log if account has auto-logging enabled
        if (!$emailMessage->emailAccount->auto_log_communications) {
            return false;
        }

        // Only log if we can find a client
        $client = $this->findClientFromEmailAddresses($emailMessage);
        return $client !== null;
    }
}