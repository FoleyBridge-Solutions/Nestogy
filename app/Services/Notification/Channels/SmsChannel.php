<?php

namespace App\Services\Notification\Channels;

use App\Services\Notification\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * SMS Notification Channel
 * 
 * Handles SMS delivery for ticket notifications using configurable SMS providers.
 * Supports multiple SMS providers (Twilio, AWS SNS, etc.) through driver pattern.
 */
class SmsChannel implements NotificationChannelInterface
{
    protected array $config;
    protected string $driver;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => config('services.sms.default', 'twilio'),
            'max_length' => 160,
            'truncate' => true,
            'enable_delivery_receipts' => false,
        ], $config);

        $this->driver = $this->config['driver'];
    }

    /**
     * Send SMS notification.
     */
    public function send(array $recipients, string $subject, string $message, array $data = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'channel' => 'sms'
        ];

        if (!$this->isAvailable()) {
            $results['errors'][] = 'SMS channel is not properly configured';
            return $results;
        }

        $validRecipients = $this->validateRecipients($recipients);

        foreach ($validRecipients as $recipient) {
            try {
                $smsMessage = $this->formatMessage($message, $data);
                $success = $this->sendSms($recipient['phone'], $smsMessage, $data);

                if ($success) {
                    $results['sent']++;
                    Log::info('SMS notification sent', [
                        'channel' => 'sms',
                        'recipient' => $this->maskPhoneNumber($recipient['phone']),
                        'message_length' => strlen($smsMessage),
                        'ticket_id' => $data['ticket']->id ?? null
                    ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'recipient' => $this->maskPhoneNumber($recipient['phone']),
                        'error' => 'SMS delivery failed'
                    ];
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recipient' => $this->maskPhoneNumber($recipient['phone'] ?? 'unknown'),
                    'error' => $e->getMessage()
                ];

                Log::error('SMS notification failed', [
                    'channel' => 'sms',
                    'recipient' => $this->maskPhoneNumber($recipient['phone'] ?? 'unknown'),
                    'error' => $e->getMessage(),
                    'ticket_id' => $data['ticket']->id ?? null
                ]);
            }
        }

        return $results;
    }

    /**
     * Check if SMS channel is available and configured.
     */
    public function isAvailable(): bool
    {
        switch ($this->driver) {
            case 'twilio':
                return !empty(config('services.twilio.sid')) && 
                       !empty(config('services.twilio.token'));
            
            case 'aws_sns':
                return !empty(config('services.aws.key')) && 
                       !empty(config('services.aws.secret'));
            
            case 'nexmo':
                return !empty(config('services.nexmo.key')) && 
                       !empty(config('services.nexmo.secret'));
            
            default:
                return false;
        }
    }

    /**
     * Get channel name.
     */
    public function getName(): string
    {
        return 'sms';
    }

    /**
     * Validate and format recipients for SMS delivery.
     */
    public function validateRecipients(array $recipients): array
    {
        $validRecipients = [];

        foreach ($recipients as $recipient) {
            $phoneData = $this->extractPhoneData($recipient);
            
            if ($phoneData && $this->isValidPhoneNumber($phoneData['phone'])) {
                $validRecipients[] = $phoneData;
            }
        }

        return $validRecipients;
    }

    /**
     * Get required configuration for SMS channel.
     */
    public function getRequiredConfig(): array
    {
        return [
            'driver' => 'SMS service provider (twilio, aws_sns, nexmo)',
            'api_credentials' => 'Provider-specific API credentials',
            'sender_number' => 'SMS sender phone number'
        ];
    }

    /**
     * Format message for SMS delivery.
     */
    public function formatMessage(string $message, array $data = []): string
    {
        // SMS messages need to be concise
        $formattedMessage = strip_tags($message);
        
        // Add basic ticket info if available
        if (isset($data['ticket'])) {
            $ticket = $data['ticket'];
            $ticketInfo = " [Ticket #{$ticket->ticket_number}]";
            $formattedMessage = $formattedMessage . $ticketInfo;
        }

        // Truncate if too long
        if ($this->config['truncate'] && strlen($formattedMessage) > $this->config['max_length']) {
            $formattedMessage = substr($formattedMessage, 0, $this->config['max_length'] - 3) . '...';
        }

        return $formattedMessage;
    }

    /**
     * Send SMS using the configured driver.
     */
    protected function sendSms(string $phoneNumber, string $message, array $data = []): bool
    {
        switch ($this->driver) {
            case 'twilio':
                return $this->sendViaTwilio($phoneNumber, $message);
            
            case 'aws_sns':
                return $this->sendViaAwsSns($phoneNumber, $message);
            
            case 'nexmo':
                return $this->sendViaNexmo($phoneNumber, $message);
            
            default:
                throw new \Exception("Unsupported SMS driver: {$this->driver}");
        }
    }

    /**
     * Send SMS via Twilio.
     */
    protected function sendViaTwilio(string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->post("https://api.twilio.com/2010-04-01/Accounts/" . config('services.twilio.sid') . "/Messages.json", [
                    'From' => config('services.twilio.from'),
                    'To' => $phoneNumber,
                    'Body' => $message
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send SMS via AWS SNS.
     */
    protected function sendViaAwsSns(string $phoneNumber, string $message): bool
    {
        // TODO: Implement AWS SNS SMS sending
        // This would require AWS SDK integration
        Log::info('AWS SNS SMS sending not yet implemented', [
            'phone' => $this->maskPhoneNumber($phoneNumber),
            'message_length' => strlen($message)
        ]);
        
        return false;
    }

    /**
     * Send SMS via Nexmo (Vonage).
     */
    protected function sendViaNexmo(string $phoneNumber, string $message): bool
    {
        // TODO: Implement Nexmo SMS sending
        Log::info('Nexmo SMS sending not yet implemented', [
            'phone' => $this->maskPhoneNumber($phoneNumber),
            'message_length' => strlen($message)
        ]);
        
        return false;
    }

    /**
     * Extract phone data from various recipient types.
     */
    protected function extractPhoneData($recipient): ?array
    {
        if (is_string($recipient)) {
            // Direct phone string
            return ['phone' => $recipient, 'name' => null, 'type' => 'phone'];
        }

        if (is_array($recipient) && isset($recipient['phone'])) {
            // Array with phone key
            return [
                'phone' => $recipient['phone'],
                'name' => $recipient['name'] ?? null,
                'type' => $recipient['type'] ?? 'array'
            ];
        }

        if ($recipient instanceof User) {
            // User model - check for phone number
            if (!empty($recipient->phone)) {
                return [
                    'phone' => $recipient->phone,
                    'name' => $recipient->name,
                    'type' => 'user',
                    'user_id' => $recipient->id
                ];
            }
        }

        if ($recipient instanceof Contact) {
            // Contact model - check for phone number
            if (!empty($recipient->phone)) {
                return [
                    'phone' => $recipient->phone,
                    'name' => $recipient->name,
                    'type' => 'contact',
                    'contact_id' => $recipient->id
                ];
            }
        }

        return null;
    }

    /**
     * Validate phone number format.
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        // Basic phone validation - should be enhanced based on requirements
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }

    /**
     * Mask phone number for logging (privacy).
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) < 4) {
            return '***';
        }
        
        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    /**
     * Set SMS driver.
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;
        $this->config['driver'] = $driver;
        return $this;
    }

    /**
     * Get current driver.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Set configuration.
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
}