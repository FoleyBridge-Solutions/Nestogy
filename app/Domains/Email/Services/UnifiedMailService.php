<?php

namespace App\Domains\Email\Services;

use App\Models\Client;
use App\Models\CompanyMailSettings;
use App\Models\MailQueue;
use App\Models\MailTemplate;
use Exception;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Throwable;

/**
 * Unified Mail Service
 *
 * Centralized email handling with:
 * - Queue management with visibility
 * - Template support
 * - Error tracking and retry logic
 * - Email tracking (opens/clicks)
 * - Multi-provider support
 * - Comprehensive logging
 */
class UnifiedMailService
{
    private const HREF_ATTRIBUTE = 'href="';

    protected array $config;

    public function __construct()
    {
        $this->config = [
            'default_from_email' => config('mail.from.address'),
            'default_from_name' => config('mail.from.name'),
            'default_mailer' => config('mail.default'),
            'track_opens' => config('mail.track_opens', true),
            'track_clicks' => config('mail.track_clicks', true),
            'queue_immediately' => config('mail.queue_immediately', false),
        ];
    }

    /**
     * Queue an email for sending
     */
    public function queue(array $data): MailQueue
    {
        // Validate required fields
        $this->validateEmailData($data);

        // Get company ID
        $companyId = $data['company_id'] ?? auth()->user()?->company_id;

        // Get company mail settings for defaults
        $companySettings = null;
        if ($companyId) {
            $companySettings = CompanyMailSettings::where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
        }

        // Create mail queue entry
        $mailQueue = MailQueue::create([
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'client_id' => $data['client_id'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),

            // Email details
            'from_email' => $data['from_email'] ?? $companySettings?->from_email ?? $this->config['default_from_email'],
            'from_name' => $data['from_name'] ?? $companySettings?->from_name ?? $this->config['default_from_name'],
            'to_email' => $data['to_email'],
            'to_name' => $data['to_name'] ?? null,
            'cc' => $data['cc'] ?? null,
            'bcc' => $data['bcc'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'subject' => $data['subject'],
            'html_body' => $data['html_body'] ?? null,
            'text_body' => $data['text_body'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'headers' => $data['headers'] ?? null,

            // Template
            'template' => $data['template'] ?? null,
            'template_data' => $data['template_data'] ?? null,

            // Queue management
            'status' => MailQueue::STATUS_PENDING,
            'priority' => $data['priority'] ?? MailQueue::PRIORITY_NORMAL,
            'max_attempts' => $data['max_attempts'] ?? 3,
            'scheduled_at' => $data['scheduled_at'] ?? null,

            // Provider
            'mailer' => $data['mailer'] ?? $this->config['default_mailer'],

            // Categorization
            'category' => $data['category'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'tags' => $data['tags'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        // Process immediately if configured or high priority
        if ($this->config['queue_immediately'] || $mailQueue->priority === MailQueue::PRIORITY_CRITICAL) {
            $this->process($mailQueue);
        }

        return $mailQueue;
    }

    /**
     * Send an email immediately (bypassing queue)
     */
    public function sendNow(array $data): bool
    {
        $mailQueue = $this->queue($data);

        return $this->process($mailQueue);
    }

    /**
     * Process a queued email
     */
    public function process(MailQueue $mailQueue): bool
    {
        if (! $mailQueue->isReadyToSend()) {
            return false;
        }

        try {
            $mailQueue->markAsProcessing();

            // Build email body from template if needed
            if ($mailQueue->template && ! $mailQueue->html_body) {
                $this->buildFromTemplate($mailQueue);
            }

            // Add tracking if enabled
            if ($this->config['track_opens'] || $this->config['track_clicks']) {
                $this->addTracking($mailQueue);
            }

            // Send the email
            $this->sendEmail($mailQueue);

            // Mark as sent
            $mailQueue->markAsSent();

            // Log to communication log if client-related
            if ($mailQueue->client_id) {
                $this->logToCommunications($mailQueue);
            }

            return true;

        } catch (Exception $e) {
            $this->handleSendFailure($mailQueue, $e);

            return false;
        }
    }

    /**
     * Send the actual email
     */
    protected function sendEmail(MailQueue $mailQueue): void
    {
        // Get company-specific mail settings
        $companyMailer = $this->getCompanyMailer($mailQueue->company_id);

        $companyMailer->send([], [], function (Message $message) use ($mailQueue) {
            // Set recipients
            $message->to($mailQueue->to_email, $mailQueue->to_name);

            if ($mailQueue->cc) {
                foreach ($mailQueue->cc as $cc) {
                    $message->cc($cc['email'], $cc['name'] ?? null);
                }
            }

            if ($mailQueue->bcc) {
                foreach ($mailQueue->bcc as $bcc) {
                    $message->bcc($bcc['email'], $bcc['name'] ?? null);
                }
            }

            // Set from
            $message->from($mailQueue->from_email, $mailQueue->from_name);

            // Set reply-to
            if ($mailQueue->reply_to) {
                $message->replyTo($mailQueue->reply_to);
            }

            // Set subject
            $message->subject($mailQueue->subject);

            // Set body
            if ($mailQueue->html_body) {
                $message->html($mailQueue->html_body);
            }

            if ($mailQueue->text_body) {
                $message->text($mailQueue->text_body);
            }

            // Add attachments
            if ($mailQueue->attachments) {
                foreach ($mailQueue->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    } elseif (isset($attachment['data'])) {
                        $message->attachData(
                            $attachment['data'],
                            $attachment['name'],
                            ['mime' => $attachment['mime'] ?? 'application/octet-stream']
                        );
                    }
                }
            }

            // Add custom headers
            if ($mailQueue->headers) {
                foreach ($mailQueue->headers as $key => $value) {
                    $message->getHeaders()->addTextHeader($key, $value);
                }
            }

            // Add tracking headers
            $message->getHeaders()->addTextHeader('X-Mail-Queue-ID', $mailQueue->uuid);
            $message->getHeaders()->addTextHeader('X-Tracking-Token', $mailQueue->tracking_token);
        });
    }

    /**
     * Build email from template
     */
    protected function buildFromTemplate(MailQueue $mailQueue): void
    {
        $template = MailTemplate::where('name', $mailQueue->template)->first();

        if (! $template) {
            throw new Exception("Email template '{$mailQueue->template}' not found");
        }

        $data = array_merge(
            $template->default_data ?? [],
            $mailQueue->template_data ?? []
        );

        // Add system variables
        $data['tracking_token'] = $mailQueue->tracking_token;
        $data['unsubscribe_url'] = route('email.unsubscribe', ['token' => $mailQueue->tracking_token]);
        $data['view_online_url'] = route('email.view', ['uuid' => $mailQueue->uuid]);

        // Render template
        $mailQueue->html_body = View::make('emails.template', [
            'template' => $template->html_template,
            'data' => $data,
        ])->render();

        if ($template->text_template) {
            $mailQueue->text_body = View::make('emails.template-text', [
                'template' => $template->text_template,
                'data' => $data,
            ])->render();
        }

        // Update subject if it contains variables
        if (str_contains($template->subject, '{{')) {
            $mailQueue->subject = $this->replaceVariables($template->subject, $data);
        }

        $mailQueue->save();
    }

    /**
     * Add tracking pixels and link tracking
     */
    protected function addTracking(MailQueue $mailQueue): void
    {
        if (! $mailQueue->html_body) {
            return;
        }

        // Add open tracking pixel
        if ($this->config['track_opens']) {
            $pixelUrl = route('email.track.open', ['token' => $mailQueue->tracking_token]);
            $pixel = '<img src="'.$pixelUrl.'" width="1" height="1" border="0" style="display:block;width:1px;height:1px;border:0;" alt="">';

            // Add before closing body tag
            $mailQueue->html_body = str_replace('</body>', $pixel.'</body>', $mailQueue->html_body);
        }

        // Add click tracking
        if ($this->config['track_clicks']) {
            $mailQueue->html_body = $this->addClickTracking($mailQueue->html_body, $mailQueue->tracking_token);
        }

        $mailQueue->save();
    }

    /**
     * Add click tracking to links
     */
    protected function addClickTracking(string $html, string $trackingToken): string
    {
        // Find all links
        preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/i', $html, $matches);

        foreach ($matches[2] as $url) {
            // Skip mailto, tel, and already tracked links
            if (preg_match('/^(mailto:|tel:|#|javascript:)/i', $url) || str_contains($url, '/email/track/click')) {
                continue;
            }

            // Create tracked URL
            $trackedUrl = route('email.track.click', [
                'token' => $trackingToken,
                'url' => base64_encode($url),
            ]);

            // Replace in HTML
            $html = str_replace(self::HREF_ATTRIBUTE.$url.'"', self::HREF_ATTRIBUTE.$trackedUrl.'"', $html);
            $html = str_replace("href='".$url."'", self::HREF_ATTRIBUTE.$trackedUrl.'"', $html);
        }

        return $html;
    }

    /**
     * Handle send failure
     */
    protected function handleSendFailure(MailQueue $mailQueue, Throwable $exception): void
    {
        $error = $exception->getMessage();
        $reason = $this->categorizeFailure($error);

        Log::error('Email send failed', [
            'queue_id' => $mailQueue->id,
            'uuid' => $mailQueue->uuid,
            'to' => $mailQueue->to_email,
            'subject' => $mailQueue->subject,
            'error' => $error,
            'reason' => $reason,
            'trace' => $exception->getTraceAsString(),
        ]);

        $mailQueue->markAsFailed($error, $reason);

        // Send notification for critical emails
        if ($mailQueue->priority === MailQueue::PRIORITY_CRITICAL) {
            $this->notifyAdminOfFailure($mailQueue, $error);
        }
    }

    /**
     * Categorize failure reason
     */
    protected function categorizeFailure(string $error): string
    {
        $error = strtolower($error);

        if (str_contains($error, 'authentication') || str_contains($error, 'credentials')) {
            return 'authentication_failed';
        }

        if (str_contains($error, 'connection') || str_contains($error, 'timeout')) {
            return 'connection_failed';
        }

        if (str_contains($error, 'invalid') && str_contains($error, 'address')) {
            return 'invalid_recipient';
        }

        if (str_contains($error, 'quota') || str_contains($error, 'limit')) {
            return 'quota_exceeded';
        }

        if (str_contains($error, 'spam') || str_contains($error, 'blocked')) {
            return 'blocked_as_spam';
        }

        if (str_contains($error, 'attachment') || str_contains($error, 'file')) {
            return 'attachment_error';
        }

        return 'unknown_error';
    }

    /**
     * Log email to communication log
     */
    protected function logToCommunications(MailQueue $mailQueue): void
    {
        try {
            \App\Models\CommunicationLog::create([
                'client_id' => $mailQueue->client_id,
                'user_id' => $mailQueue->user_id,
                'contact_id' => $mailQueue->contact_id,
                'type' => 'outbound',
                'channel' => 'email',
                'contact_name' => $mailQueue->to_name,
                'contact_email' => $mailQueue->to_email,
                'subject' => $mailQueue->subject,
                'notes' => "Email sent via {$mailQueue->mailer} provider. Category: {$mailQueue->category}",
                'follow_up_required' => false,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log email to communications', [
                'queue_id' => $mailQueue->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate email data
     */
    protected function validateEmailData(array $data): void
    {
        if (empty($data['to_email'])) {
            throw new Exception('Recipient email is required');
        }

        if (empty($data['subject'])) {
            throw new Exception('Email subject is required');
        }

        if (empty($data['html_body']) && empty($data['text_body']) && empty($data['template'])) {
            throw new Exception('Email body or template is required');
        }

        if (! filter_var($data['to_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid recipient email address');
        }

        if (! empty($data['from_email']) && ! filter_var($data['from_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid from email address');
        }
    }

    /**
     * Replace variables in string
     */
    protected function replaceVariables(string $string, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $string = str_replace('{{'.$key.'}}', $value, $string);
                $string = str_replace('{{ '.$key.' }}', $value, $string);
            }
        }

        return $string;
    }

    /**
     * Notify admin of critical email failure
     */
    protected function notifyAdminOfFailure(MailQueue $mailQueue, string $error): void
    {
        // This would send a notification to admins
        // For now, just log it
        Log::critical('Critical email failed to send', [
            'queue_id' => $mailQueue->id,
            'to' => $mailQueue->to_email,
            'subject' => $mailQueue->subject,
            'error' => $error,
        ]);
    }

    /**
     * Retry failed emails
     */
    public function retryFailed(): int
    {
        $retried = 0;

        $failed = MailQueue::where('status', MailQueue::STATUS_FAILED)
            ->where('attempts', '<', DB::raw('max_attempts'))
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->limit(100)
            ->get();

        foreach ($failed as $mailQueue) {
            if ($this->process($mailQueue)) {
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * Process pending emails
     */
    public function processPending(int $limit = 100): int
    {
        $processed = 0;

        $pending = MailQueue::where('status', MailQueue::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($pending as $mailQueue) {
            if ($this->process($mailQueue)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Cancel a queued email
     */
    public function cancel(MailQueue $mailQueue): bool
    {
        if (in_array($mailQueue->status, [MailQueue::STATUS_SENT, MailQueue::STATUS_PROCESSING])) {
            return false;
        }

        $mailQueue->update(['status' => MailQueue::STATUS_CANCELLED]);

        return true;
    }

    /**
     * Get company-specific mailer
     */
    protected function getCompanyMailer(?int $companyId)
    {
        if (! $companyId) {
            // Fallback to default mailer if no company
            return Mail::mailer();
        }

        // Check cache first
        $cacheKey = "company_mailer_{$companyId}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get company mail settings
        $settings = CompanyMailSettings::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $settings) {
            // Fallback to default mailer if no settings
            return Mail::mailer();
        }

        // Create dynamic mailer configuration
        $configKey = "mail.mailers.company_{$companyId}";
        config([$configKey => $settings->getMailConfig()]);

        // Set from address configuration
        config([
            "mail.from.address.company_{$companyId}" => $settings->from_email,
            "mail.from.name.company_{$companyId}" => $settings->from_name,
        ]);

        // Create the mailer
        $mailer = Mail::mailer("company_{$companyId}");

        // Cache for 5 minutes
        Cache::put($cacheKey, $mailer, now()->addMinutes(5));

        return $mailer;
    }

    /**
     * Clear company mailer cache
     */
    public function clearCompanyMailerCache(int $companyId): void
    {
        Cache::forget("company_mailer_{$companyId}");
    }

    /**
     * Get email statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = MailQueue::query();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total' => $query->count(),
            'sent' => $query->clone()->where('status', MailQueue::STATUS_SENT)->count(),
            'failed' => $query->clone()->where('status', MailQueue::STATUS_FAILED)->count(),
            'pending' => $query->clone()->where('status', MailQueue::STATUS_PENDING)->count(),
            'opened' => $query->clone()->whereNotNull('opened_at')->count(),
            'clicked' => $query->clone()->where('click_count', '>', 0)->count(),
            'open_rate' => $query->clone()->where('status', MailQueue::STATUS_SENT)->count() > 0
                ? round($query->clone()->whereNotNull('opened_at')->count() / $query->clone()->where('status', MailQueue::STATUS_SENT)->count() * 100, 2)
                : 0,
            'click_rate' => $query->clone()->whereNotNull('opened_at')->count() > 0
                ? round($query->clone()->where('click_count', '>', 0)->count() / $query->clone()->whereNotNull('opened_at')->count() * 100, 2)
                : 0,
        ];
    }
}
