<?php

namespace App\Domains\Email\Services;

use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailConnectionTestService
{
    /**
     * Test SMTP connection with provided settings
     */
    public function testConnection(array $settings, ?string $testEmailAddress = null): array
    {
        try {
            // Store original mail configuration
            $originalConfig = $this->storeOriginalMailConfig();

            try {
                // Set temporary mail configuration
                $this->setTemporaryMailConfig($settings);

                // Phase 1: Test basic connection and authentication
                $connectionResult = $this->testBasicConnection($settings);
                if (! $connectionResult['success']) {
                    return $connectionResult;
                }

                // Phase 2: Send test email if address provided
                if ($testEmailAddress) {
                    $emailResult = $this->sendTestEmail($settings, $testEmailAddress);
                    if (! $emailResult['success']) {
                        return $emailResult;
                    }
                }

                return [
                    'success' => true,
                    'message' => 'SMTP connection test successful',
                    'details' => [
                        'connection' => 'Connected to '.$settings['smtp_host'].':'.$settings['smtp_port'],
                        'authentication' => 'Authentication successful',
                        'test_email' => $testEmailAddress ? 'Test email sent successfully' : 'Test email not requested',
                    ],
                ];

            } finally {
                // Always restore original configuration
                $this->restoreOriginalMailConfig($originalConfig);
            }

        } catch (Exception $e) {
            Log::error('Email connection test failed', [
                'error' => $e->getMessage(),
                'settings' => $this->sanitizeSettingsForLog($settings),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
                'error_type' => 'exception',
                'details' => [],
            ];
        }
    }

    /**
     * Test basic SMTP connection using Laravel's mail system
     */
    protected function testBasicConnection(array $settings): array
    {
        try {
            // Create a simple test mailable
            $testMailable = new class extends Mailable
            {
                public function build()
                {
                    return $this->subject('Connection Test')
                        ->html('<p>Connection test</p>');
                }
            };

            // Configure the mailable with test settings
            $testMailable->to('test@example.com');

            // Try to render and prepare the message (this tests configuration)
            $message = $testMailable->render();

            // Test the SMTP connection by getting the mailer instance
            $mailer = Mail::mailer();
            $transport = $mailer->getSymfonyTransport();

            // Start the transport to test connection
            $transport->start();

            return [
                'success' => true,
                'message' => 'SMTP connection and authentication successful',
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            return [
                'success' => false,
                'message' => 'SMTP connection failed: '.$errorMessage,
                'error_type' => 'smtp_error',
                'details' => [
                    'host' => $settings['smtp_host'],
                    'port' => $settings['smtp_port'],
                    'suggestion' => $this->getSuggestionForError($errorMessage),
                ],
            ];
        }
    }

    /**
     * Send test email using Laravel's mail system
     */
    public function sendTestEmail(array $settings, string $toEmail): array
    {
        try {
            $testMailable = new class($settings) extends Mailable
            {
                private $emailSettings;

                public function __construct($settings)
                {
                    $this->emailSettings = $settings;
                }

                public function build()
                {
                    $fromEmail = $this->emailSettings['mail_from_email'] ?? $this->emailSettings['smtp_username'];
                    $fromName = $this->emailSettings['mail_from_name'] ?? 'Nestogy MSP Platform';

                    return $this->from($fromEmail, $fromName)
                        ->subject('Nestogy Email Configuration Test')
                        ->html($this->getTestEmailBody())
                        ->text($this->getTestEmailTextBody());
                }

                private function getTestEmailBody(): string
                {
                    return '
                        <h2>Email Configuration Test</h2>
                        <p>This is a test email from your Nestogy MSP platform.</p>
                        <p><strong>Test Details:</strong></p>
                        <ul>
                            <li>Date: '.now()->format('Y-m-d H:i:s T').'</li>
                            <li>Status: Email configuration is working correctly</li>
                            <li>Platform: Nestogy MSP</li>
                        </ul>
                        <p>If you received this email, your SMTP configuration is working properly.</p>
                        <hr>
                        <p><small>This email was sent automatically by the Nestogy MSP platform to test your email configuration.</small></p>
                    ';
                }

                private function getTestEmailTextBody(): string
                {
                    return "Email Configuration Test\n\n".
                           "This is a test email from your Nestogy MSP platform.\n\n".
                           "Test Details:\n".
                           '- Date: '.now()->format('Y-m-d H:i:s T')."\n".
                           "- Status: Email configuration is working correctly\n".
                           "- Platform: Nestogy MSP\n\n".
                           "If you received this email, your SMTP configuration is working properly.\n\n".
                           'This email was sent automatically by the Nestogy MSP platform to test your email configuration.';
                }
            };

            Mail::to($toEmail)->send($testMailable);

            return [
                'success' => true,
                'message' => 'Test email sent successfully',
                'details' => [
                    'to' => $toEmail,
                    'from' => $settings['mail_from_email'] ?? $settings['smtp_username'],
                ],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
                'error_type' => 'email_error',
                'details' => [
                    'suggestion' => $this->getSuggestionForError($e->getMessage()),
                ],
            ];
        }
    }

    /**
     * Store original mail configuration
     */
    protected function storeOriginalMailConfig(): array
    {
        return [
            'mailer' => Config::get('mail.default'),
            'host' => Config::get('mail.mailers.smtp.host'),
            'port' => Config::get('mail.mailers.smtp.port'),
            'username' => Config::get('mail.mailers.smtp.username'),
            'password' => Config::get('mail.mailers.smtp.password'),
            'encryption' => Config::get('mail.mailers.smtp.encryption'),
            'from_address' => Config::get('mail.from.address'),
            'from_name' => Config::get('mail.from.name'),
        ];
    }

    /**
     * Set temporary mail configuration for testing
     */
    protected function setTemporaryMailConfig(array $settings): void
    {
        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings['smtp_host'],
            'mail.mailers.smtp.port' => $settings['smtp_port'],
            'mail.mailers.smtp.username' => $settings['smtp_username'],
            'mail.mailers.smtp.password' => $settings['smtp_password'],
            'mail.mailers.smtp.encryption' => $settings['smtp_encryption'] ?? null,
            'mail.mailers.smtp.timeout' => 30,
            'mail.from.address' => $settings['mail_from_email'] ?? $settings['smtp_username'],
            'mail.from.name' => $settings['mail_from_name'] ?? 'Nestogy MSP Platform',
        ]);
    }

    /**
     * Restore original mail configuration
     */
    protected function restoreOriginalMailConfig(array $originalConfig): void
    {
        Config::set([
            'mail.default' => $originalConfig['mailer'],
            'mail.mailers.smtp.host' => $originalConfig['host'],
            'mail.mailers.smtp.port' => $originalConfig['port'],
            'mail.mailers.smtp.username' => $originalConfig['username'],
            'mail.mailers.smtp.password' => $originalConfig['password'],
            'mail.mailers.smtp.encryption' => $originalConfig['encryption'],
            'mail.from.address' => $originalConfig['from_address'],
            'mail.from.name' => $originalConfig['from_name'],
        ]);
    }

    /**
     * Get common email provider presets
     */
    public function getCommonProviderPresets(): array
    {
        return [
            'gmail' => [
                'name' => 'Gmail',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'instructions' => 'Use your Gmail address and an App Password (not your regular password)',
            ],
            'outlook' => [
                'name' => 'Outlook.com / Hotmail',
                'smtp_host' => 'smtp-mail.outlook.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'imap_host' => 'outlook.office365.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'instructions' => 'Use your full email address and password',
            ],
            'office365' => [
                'name' => 'Office 365',
                'smtp_host' => 'smtp.office365.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'imap_host' => 'outlook.office365.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'instructions' => 'Use your Office 365 email address and password',
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'smtp_host' => 'smtp.mail.yahoo.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'imap_host' => 'imap.mail.yahoo.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'instructions' => 'Use your Yahoo email and an App Password',
            ],
            'godaddy' => [
                'name' => 'GoDaddy Email',
                'smtp_host' => 'smtpout.secureserver.net',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'imap_host' => 'imap.secureserver.net',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'instructions' => 'Use your full email address and password',
            ],
        ];
    }

    /**
     * Validate SMTP settings format
     */
    public function validateSMTPSettings(array $settings): array
    {
        $errors = [];

        // Required fields
        if (empty($settings['smtp_host'])) {
            $errors['smtp_host'] = 'SMTP host is required';
        } elseif (! filter_var(gethostbyname($settings['smtp_host']), FILTER_VALIDATE_IP)) {
            $errors['smtp_host'] = 'SMTP host appears to be invalid';
        }

        if (empty($settings['smtp_port'])) {
            $errors['smtp_port'] = 'SMTP port is required';
        } elseif (! is_numeric($settings['smtp_port']) || $settings['smtp_port'] < 1 || $settings['smtp_port'] > 65535) {
            $errors['smtp_port'] = 'SMTP port must be between 1 and 65535';
        }

        if (empty($settings['smtp_username'])) {
            $errors['smtp_username'] = 'SMTP username is required';
        }

        if (empty($settings['smtp_password'])) {
            $errors['smtp_password'] = 'SMTP password is required';
        }

        if (! empty($settings['mail_from_email']) && ! filter_var($settings['mail_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['mail_from_email'] = 'From email address is invalid';
        }

        if (! empty($settings['smtp_encryption']) && ! in_array($settings['smtp_encryption'], ['tls', 'ssl'])) {
            $errors['smtp_encryption'] = 'Encryption must be TLS or SSL';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get suggestion for common errors
     */
    protected function getSuggestionForError(string $error): string
    {
        $error = strtolower($error);

        if (strpos($error, 'connection timed out') !== false || strpos($error, 'timeout') !== false) {
            return 'Check your firewall settings and ensure the SMTP port is not blocked';
        }

        if (strpos($error, 'connection refused') !== false || strpos($error, 'could not connect') !== false) {
            return 'Verify the SMTP host and port are correct';
        }

        if (strpos($error, 'authentication failed') !== false || strpos($error, 'invalid credentials') !== false || strpos($error, 'username') !== false) {
            return 'Check your username and password. For Gmail, use an App Password instead of your regular password';
        }

        if (strpos($error, 'ssl') !== false || strpos($error, 'tls') !== false) {
            return 'Try switching between TLS and SSL encryption, or check if encryption is required';
        }

        if (strpos($error, 'certificate') !== false) {
            return 'There may be an SSL certificate issue. Contact your email provider for assistance';
        }

        if (strpos($error, 'relay') !== false || strpos($error, 'not permitted') !== false) {
            return 'Your email provider may not allow relay. Check if authentication is required or contact your provider';
        }

        return 'Please check your email server settings and try again';
    }

    /**
     * Sanitize settings for logging (remove sensitive data)
     */
    protected function sanitizeSettingsForLog(array $settings): array
    {
        $sanitized = $settings;

        // Remove sensitive information
        if (isset($sanitized['smtp_password'])) {
            $sanitized['smtp_password'] = '[REDACTED]';
        }
        if (isset($sanitized['imap_password'])) {
            $sanitized['imap_password'] = '[REDACTED]';
        }

        return $sanitized;
    }
}
