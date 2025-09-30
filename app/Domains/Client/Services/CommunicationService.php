<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Models\DunningAction;
use App\Models\CollectionNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Mail\Mailable;

/**
 * Communication Service
 * 
 * Handles automated multi-channel communications for dunning management
 * including emails, SMS, phone calls, and portal notifications with
 * template management, personalization, and compliance tracking.
 */
class CommunicationService
{
    protected array $channelPriority = [
        'email' => 1,
        'sms' => 2,
        'portal_notification' => 3,
        'phone_call' => 4,
        'letter' => 5
    ];

    protected array $templates = [];
    protected string $cachePrefix = 'communication:';
    protected int $cacheTtl = 3600;

    /**
     * Send multi-channel dunning communication.
     */
    public function sendDunningCommunication(
        Client $client,
        string $messageType,
        array $templateData = [],
        array $options = []
    ): array {
        $results = [
            'sent' => [],
            'failed' => [],
            'skipped' => [],
            'total_channels' => 0
        ];

        // Get client communication preferences
        $preferences = $this->getClientCommunicationPreferences($client);
        
        // Check TCPA compliance
        if (!$this->checkTcpaCompliance($client, $options['channels'] ?? [])) {
            Log::warning('Communication blocked by TCPA compliance', [
                'client_id' => $client->id,
                'message_type' => $messageType
            ]);
            return $results;
        }

        // Determine channels to use
        $channels = $this->determineOptimalChannels($client, $preferences, $options);
        $results['total_channels'] = count($channels);

        foreach ($channels as $channel) {
            try {
                $sent = $this->sendToChannel($client, $channel, $messageType, $templateData, $options);
                
                if ($sent) {
                    $results['sent'][] = $channel;
                    
                    // Record successful communication
                    $this->recordCommunication($client, $channel, $messageType, 'sent', $templateData);
                } else {
                    $results['failed'][] = $channel;
                }
            } catch (\Exception $e) {
                $results['failed'][] = $channel;
                Log::error('Communication failed', [
                    'client_id' => $client->id,
                    'channel' => $channel,
                    'message_type' => $messageType,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Get client communication preferences.
     */
    protected function getClientCommunicationPreferences(Client $client): array
    {
        $cacheKey = $this->cachePrefix . "preferences:{$client->id}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($client) {
            // Get from client record or use defaults
            return [
                'email' => [
                    'enabled' => true,
                    'address' => $client->email,
                    'opt_out' => false,
                    'bounce_count' => 0
                ],
                'sms' => [
                    'enabled' => !empty($client->phone),
                    'number' => $client->phone,
                    'opt_out' => false,
                    'consent_given' => $client->sms_consent ?? false
                ],
                'phone_call' => [
                    'enabled' => !empty($client->phone),
                    'number' => $client->phone,
                    'do_not_call' => $client->do_not_call ?? false,
                    'best_time' => $client->preferred_call_time ?? 'business_hours'
                ],
                'portal_notification' => [
                    'enabled' => true
                ],
                'letter' => [
                    'enabled' => !empty($client->mailing_address),
                    'address' => $client->mailing_address
                ]
            ];
        });
    }

    /**
     * Check TCPA compliance for communications.
     */
    protected function checkTcpaCompliance(Client $client, array $channels): bool
    {
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'sms':
                    if (!$client->sms_consent || $client->sms_opt_out) {
                        return false;
                    }
                    break;
                    
                case 'phone_call':
                    if ($client->do_not_call) {
                        return false;
                    }
                    break;
            }
        }

        // Check communication frequency limits
        return $this->checkCommunicationFrequencyLimits($client);
    }

    /**
     * Check communication frequency limits to avoid harassment.
     */
    protected function checkCommunicationFrequencyLimits(Client $client): bool
    {
        $today = Carbon::today();
        
        // Get today's communications
        $todayCount = DunningAction::where('client_id', $client->id)
            ->whereDate('created_at', $today)
            ->whereIn('action_type', [
                DunningAction::ACTION_EMAIL,
                DunningAction::ACTION_SMS,
                DunningAction::ACTION_PHONE_CALL
            ])
            ->count();

        // Maximum 3 communications per day
        if ($todayCount >= 3) {
            return false;
        }

        // Check for Sunday restrictions (no collection calls on Sunday)
        if ($today->isSunday()) {
            return false;
        }

        // Check for late hours (no calls after 9 PM or before 8 AM)
        $currentHour = Carbon::now()->hour;
        if ($currentHour < 8 || $currentHour > 21) {
            return false;
        }

        return true;
    }

    /**
     * Determine optimal communication channels based on preferences and effectiveness.
     */
    protected function determineOptimalChannels(
        Client $client, 
        array $preferences, 
        array $options = []
    ): array {
        if (!empty($options['channels'])) {
            return array_intersect($options['channels'], array_keys($preferences));
        }

        $channels = [];
        
        // Always prefer email if available
        if ($preferences['email']['enabled'] && !$preferences['email']['opt_out']) {
            $channels[] = 'email';
        }

        // Add SMS for urgent communications
        if ($preferences['sms']['enabled'] && $preferences['sms']['consent_given'] && 
            !empty($options['urgent'])) {
            $channels[] = 'sms';
        }

        // Add portal notification as fallback
        if ($preferences['portal_notification']['enabled']) {
            $channels[] = 'portal_notification';
        }

        return $channels;
    }

    /**
     * Send communication to a specific channel.
     */
    protected function sendToChannel(
        Client $client,
        string $channel,
        string $messageType,
        array $templateData,
        array $options = []
    ): bool {
        switch ($channel) {
            case 'email':
                return $this->sendEmail($client, $messageType, $templateData, $options);
            case 'sms':
                return $this->sendSms($client, $messageType, $templateData, $options);
            case 'phone_call':
                return $this->makePhoneCall($client, $messageType, $templateData, $options);
            case 'portal_notification':
                return $this->sendPortalNotification($client, $messageType, $templateData, $options);
            case 'letter':
                return $this->sendLetter($client, $messageType, $templateData, $options);
            default:
                throw new \InvalidArgumentException("Unknown communication channel: {$channel}");
        }
    }

    /**
     * Send email communication.
     */
    protected function sendEmail(
        Client $client,
        string $messageType,
        array $templateData,
        array $options = []
    ): bool {
        try {
            $template = $this->getEmailTemplate($messageType);
            $personalizedContent = $this->personalizeContent($template, $client, $templateData);

            $mailable = new DunningEmailMailable(
                $client,
                $personalizedContent['subject'],
                $personalizedContent['body'],
                $personalizedContent['attachments'] ?? []
            );

            Mail::to($client->email)->send($mailable);

            Log::info('Email sent successfully', [
                'client_id' => $client->id,
                'message_type' => $messageType,
                'to' => $client->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'client_id' => $client->id,
                'message_type' => $messageType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send SMS communication.
     */
    protected function sendSms(
        Client $client,
        string $messageType,
        array $templateData,
        array $options = []
    ): bool {
        try {
            $template = $this->getSmsTemplate($messageType);
            $personalizedContent = $this->personalizeContent($template, $client, $templateData);

            // Call SMS service API
            $response = Http::post('https://api.smsservice.com/send', [
                'to' => $client->phone,
                'message' => $personalizedContent['body'],
                'from' => config('dunning.sms_sender_id', 'NESTOGY')
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'client_id' => $client->id,
                    'message_type' => $messageType,
                    'to' => $client->phone
                ]);
                return true;
            }

            throw new \Exception('SMS API returned error: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'client_id' => $client->id,
                'message_type' => $messageType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Make automated phone call.
     */
    protected function makePhoneCall(
        Client $client,
        string $messageType,
        array $templateData,
        array $options = []
    ): bool {
        try {
            $template = $this->getPhoneTemplate($messageType);
            $personalizedContent = $this->personalizeContent($template, $client, $templateData);

            // Call voice service API
            $response = Http::post('https://api.voiceservice.com/call', [
                'to' => $client->phone,
                'message' => $personalizedContent['body'],
                'voice' => 'female',
                'callback_url' => route('dunning.call.callback')
            ]);

            if ($response->successful()) {
                Log::info('Phone call initiated successfully', [
                    'client_id' => $client->id,
                    'message_type' => $messageType,
                    'to' => $client->phone
                ]);
                return true;
            }

            throw new \Exception('Voice API returned error: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Phone call failed', [
                'client_id' => $client->id,
                'message_type' => $messageType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send portal notification.
     */
    protected function sendPortalNotification(
        Client $client,
        string $messageType,
        array $templateData,
        array $options = []
    ): bool {
        try {
            $template = $this->getPortalTemplate($messageType);
            $personalizedContent = $this->personalizeContent($template, $client, $templateData);

            // Create portal notification record
            DB::table('portal_notifications')->insert([
                'client_id' => $client->id,
                'title' => $personalizedContent['subject'],
                'message' => $personalizedContent['body'],
                'type' => 'collection_notice',
                'priority' => $options['priority'] ?? 'normal',
                'read' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            Log::info('Portal notification sent successfully', [
                'client_id' => $client->id,
                'message_type' => $messageType
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Portal notification failed', [
                'client_id' => $client->id,
                'message_type' => $messageType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send physical letter.
     */
    protected function sendLetter(
        Client $client,
        string $messageType,
        array $templateData,
        array $options = []
    ): bool {
        try {
            $template = $this->getLetterTemplate($messageType);
            $personalizedContent = $this->personalizeContent($template, $client, $templateData);

            // Queue letter for printing and mailing service
            DB::table('letter_queue')->insert([
                'client_id' => $client->id,
                'recipient_name' => $client->name,
                'recipient_address' => $client->mailing_address,
                'content' => $personalizedContent['body'],
                'message_type' => $messageType,
                'status' => 'queued',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            Log::info('Letter queued successfully', [
                'client_id' => $client->id,
                'message_type' => $messageType
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Letter queueing failed', [
                'client_id' => $client->id,
                'message_type' => $messageType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get email template for message type.
     */
    protected function getEmailTemplate(string $messageType): array
    {
        $templates = [
            'initial_reminder' => [
                'subject' => 'Payment Reminder - Account #{account_number}',
                'body' => $this->getEmailTemplateBody('initial_reminder'),
                'attachments' => []
            ],
            'final_notice' => [
                'subject' => 'FINAL NOTICE - Account #{account_number}',
                'body' => $this->getEmailTemplateBody('final_notice'),
                'attachments' => ['statement.pdf']
            ],
            'service_suspension_warning' => [
                'subject' => 'Service Suspension Warning - Account #{account_number}',
                'body' => $this->getEmailTemplateBody('suspension_warning'),
                'attachments' => []
            ],
            'payment_plan_offer' => [
                'subject' => 'Payment Plan Available - Account #{account_number}',
                'body' => $this->getEmailTemplateBody('payment_plan_offer'),
                'attachments' => []
            ]
        ];

        return $templates[$messageType] ?? throw new \InvalidArgumentException("Unknown email template: {$messageType}");
    }

    /**
     * Get SMS template for message type.
     */
    protected function getSmsTemplate(string $messageType): array
    {
        $templates = [
            'payment_reminder' => [
                'body' => "NESTOGY: Your account #{account_number} has a balance of {\$past_due_amount}. Please pay by {due_date} to avoid service interruption. Pay online: {payment_link}"
            ],
            'service_suspended' => [
                'body' => "NESTOGY: Your service has been suspended due to non-payment. Balance: {\$past_due_amount}. Pay now: {payment_link}"
            ],
            'payment_received' => [
                'body' => "NESTOGY: Thank you for your payment of {\$payment_amount}. Your services will be restored within 24 hours."
            ]
        ];

        return $templates[$messageType] ?? throw new \InvalidArgumentException("Unknown SMS template: {$messageType}");
    }

    /**
     * Get phone template for message type.
     */
    protected function getPhoneTemplate(string $messageType): array
    {
        $templates = [
            'payment_reminder' => [
                'body' => "Hello {client_name}. This is an automated call from Nestogy regarding your account #{account_number}. Your account has a past due balance of {\$past_due_amount}. Please call us at 1-800-NESTOGY to make a payment or set up a payment plan. Thank you."
            ],
            'final_notice' => [
                'body' => "This is a final notice call from Nestogy for {client_name}. Your account #{account_number} has a past due balance of {\$past_due_amount}. Service suspension is imminent. Please call us immediately at 1-800-NESTOGY to avoid interruption."
            ]
        ];

        return $templates[$messageType] ?? throw new \InvalidArgumentException("Unknown phone template: {$messageType}");
    }

    /**
     * Get portal notification template.
     */
    protected function getPortalTemplate(string $messageType): array
    {
        $templates = [
            'payment_reminder' => [
                'subject' => 'Payment Due',
                'body' => "Your account has a past due balance of {\$past_due_amount}. Please make a payment to avoid service interruption."
            ],
            'service_suspended' => [
                'subject' => 'Service Suspended',
                'body' => "Your service has been suspended due to non-payment. Please make a payment of {\$past_due_amount} to restore service."
            ]
        ];

        return $templates[$messageType] ?? throw new \InvalidArgumentException("Unknown portal template: {$messageType}");
    }

    /**
     * Get letter template for message type.
     */
    protected function getLetterTemplate(string $messageType): array
    {
        $templates = [
            'final_demand' => [
                'body' => $this->getLetterTemplateBody('final_demand')
            ],
            'legal_notice' => [
                'body' => $this->getLetterTemplateBody('legal_notice')
            ]
        ];

        return $templates[$messageType] ?? throw new \InvalidArgumentException("Unknown letter template: {$messageType}");
    }

    /**
     * Get email template body.
     */
    protected function getEmailTemplateBody(string $templateType): string
    {
        $templates = [
            'initial_reminder' => "
Dear {client_name},

This is a friendly reminder that your account #{account_number} has a past due balance of {\$past_due_amount}.

Payment was due on {due_date}. To avoid service interruption, please make your payment as soon as possible.

You can pay online at: {payment_link}
Or call us at: 1-800-NESTOGY

Thank you for your prompt attention to this matter.

Best regards,
Nestogy Billing Department
            ",
            'final_notice' => "
Dear {client_name},

FINAL NOTICE - IMMEDIATE ACTION REQUIRED

Your account #{account_number} has a seriously past due balance of {\$past_due_amount}.

If payment is not received within 5 business days, your service will be suspended and additional fees may apply.

Pay immediately at: {payment_link}
Or call us at: 1-800-NESTOGY

We want to help. Contact us to discuss payment arrangements.

Sincerely,
Nestogy Collections Department
            ",
            'suspension_warning' => "
Dear {client_name},

Your VoIP service will be suspended in 48 hours due to non-payment.

Account: #{account_number}
Balance: {\$past_due_amount}

To prevent suspension:
1. Pay online: {payment_link}
2. Call us: 1-800-NESTOGY
3. Set up a payment plan

Emergency 911 service will remain active, but all other features will be disabled.

Urgent - Act now to avoid service interruption.

Nestogy Collections Team
            "
        ];

        return $templates[$templateType] ?? '';
    }

    /**
     * Get letter template body.
     */
    protected function getLetterTemplateBody(string $templateType): string
    {
        $templates = [
            'final_demand' => "
FINAL DEMAND FOR PAYMENT

{client_name}
{client_address}

Account: #{account_number}
Amount Due: {\$past_due_amount}
Due Date: {due_date}

This is formal demand for payment of the above amount. If payment is not received within 10 days from the date of this letter, we may:

1. Suspend your service immediately
2. Report this delinquency to credit agencies
3. Pursue collection through legal means
4. Recover equipment at your expense

Pay now to avoid these consequences.

NESTOGY COLLECTIONS DEPARTMENT
            "
        ];

        return $templates[$templateType] ?? '';
    }

    /**
     * Personalize content with client and template data.
     */
    protected function personalizeContent(array $template, Client $client, array $templateData): array
    {
        $personalizedTemplate = $template;
        
        // Merge client data with template data
        $data = array_merge([
            'client_name' => $client->name,
            'account_number' => $client->account_number,
            'client_address' => $client->mailing_address,
            'payment_link' => $this->generatePaymentLink($client),
            'due_date' => Carbon::now()->addDays(10)->format('M j, Y')
        ], $templateData);

        // Replace placeholders in subject and body
        foreach ($personalizedTemplate as $key => $content) {
            if (is_string($content)) {
                foreach ($data as $placeholder => $value) {
                    $personalizedTemplate[$key] = str_replace(
                        '{' . $placeholder . '}',
                        $value,
                        $personalizedTemplate[$key]
                    );
                }
            }
        }

        return $personalizedTemplate;
    }

    /**
     * Generate secure payment link for client.
     */
    protected function generatePaymentLink(Client $client): string
    {
        $token = encrypt([
            'client_id' => $client->id,
            'expires' => Carbon::now()->addDays(30)->timestamp
        ]);

        return url("/pay/{$token}");
    }

    /**
     * Record communication in dunning actions.
     */
    protected function recordCommunication(
        Client $client,
        string $channel,
        string $messageType,
        string $status,
        array $templateData
    ): void {
        $actionType = match ($channel) {
            'email' => DunningAction::ACTION_EMAIL,
            'sms' => DunningAction::ACTION_SMS,
            'phone_call' => DunningAction::ACTION_PHONE_CALL,
            'portal_notification' => DunningAction::ACTION_PORTAL_NOTIFICATION,
            'letter' => DunningAction::ACTION_LETTER,
            default => DunningAction::ACTION_EMAIL
        };

        DunningAction::create([
            'client_id' => $client->id,
            'action_type' => $actionType,
            'status' => $status === 'sent' ? DunningAction::STATUS_COMPLETED : DunningAction::STATUS_FAILED,
            'message_type' => $messageType,
            'template_data' => $templateData,
            'channel_data' => [
                'channel' => $channel,
                'recipient' => $this->getRecipientInfo($client, $channel)
            ],
            'executed_at' => Carbon::now(),
            'created_by' => auth()->id() ?? 1
        ]);
    }

    /**
     * Get recipient information for a channel.
     */
    protected function getRecipientInfo(Client $client, string $channel): string
    {
        return match ($channel) {
            'email' => $client->email,
            'sms', 'phone_call' => $client->phone,
            'portal_notification' => 'Portal User ID: ' . $client->id,
            'letter' => $client->mailing_address,
            default => $client->email
        };
    }

    /**
     * Process communication responses and callbacks.
     */
    public function processResponse(string $channel, array $responseData): void
    {
        try {
            // Find the corresponding dunning action
            $action = DunningAction::where('external_id', $responseData['message_id'] ?? null)
                ->where('action_type', $this->getActionTypeFromChannel($channel))
                ->first();

            if (!$action) {
                Log::warning('No matching dunning action found for response', $responseData);
                return;
            }

            // Update action with response
            $action->update([
                'responded_at' => Carbon::now(),
                'response_data' => $responseData,
                'response_type' => $responseData['type'] ?? 'unknown'
            ]);

            // Create collection note if significant response
            if (in_array($responseData['type'] ?? '', ['payment_promise', 'dispute', 'callback_request'])) {
                CollectionNote::create([
                    'client_id' => $action->client_id,
                    'dunning_action_id' => $action->id,
                    'note_type' => CollectionNote::TYPE_CLIENT_RESPONSE,
                    'content' => "Client responded via {$channel}: " . ($responseData['message'] ?? ''),
                    'outcome' => $this->mapResponseToOutcome($responseData['type'] ?? ''),
                    'requires_attention' => true,
                    'created_by' => 1
                ]);
            }

            Log::info('Communication response processed', [
                'action_id' => $action->id,
                'channel' => $channel,
                'response_type' => $responseData['type'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process communication response', [
                'channel' => $channel,
                'response_data' => $responseData,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get action type from channel name.
     */
    protected function getActionTypeFromChannel(string $channel): string
    {
        return match ($channel) {
            'email' => DunningAction::ACTION_EMAIL,
            'sms' => DunningAction::ACTION_SMS,
            'phone_call' => DunningAction::ACTION_PHONE_CALL,
            'portal_notification' => DunningAction::ACTION_PORTAL_NOTIFICATION,
            'letter' => DunningAction::ACTION_LETTER,
            default => DunningAction::ACTION_EMAIL
        };
    }

    /**
     * Map response type to collection note outcome.
     */
    protected function mapResponseToOutcome(string $responseType): string
    {
        return match ($responseType) {
            'payment_promise' => CollectionNote::OUTCOME_PROMISE_TO_PAY,
            'dispute' => CollectionNote::OUTCOME_DISPUTE_RAISED,
            'callback_request' => CollectionNote::OUTCOME_CALLBACK_REQUESTED,
            'opt_out' => CollectionNote::OUTCOME_OPTED_OUT,
            default => CollectionNote::OUTCOME_CLIENT_CONTACTED
        };
    }

    /**
     * Clear communication cache for a client.
     */
    public function clearClientCache(Client $client): void
    {
        $cacheKey = $this->cachePrefix . "preferences:{$client->id}";
        Cache::forget($cacheKey);
    }
}

/**
 * Mailable class for dunning emails.
 */
class DunningEmailMailable extends Mailable
{
    protected Client $client;
    protected string $emailSubject;
    protected string $emailBody;
    protected array $emailAttachments;

    public function __construct(Client $client, string $subject, string $body, array $attachments = [])
    {
        $this->client = $client;
        $this->emailSubject = $subject;
        $this->emailBody = $body;
        $this->emailAttachments = $attachments;
    }

    public function build()
    {
        $email = $this->subject($this->emailSubject)
            ->view('emails.dunning.template')
            ->with([
                'client' => $this->client,
                'body' => $this->emailBody
            ]);

        // Add attachments
        foreach ($this->emailAttachments as $attachment) {
            $email->attach(storage_path("app/dunning/attachments/{$attachment}"));
        }

        return $email;
    }
}