<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Log;
use SMTP2GOMailer\SMTP2GOMailer;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class Smtp2goTransport extends AbstractTransport
{
    /**
     * The SMTP2GO API key.
     */
    protected string $apiKey;

    /**
     * The SMTP2GO Mailer instance.
     */
    protected SMTP2GOMailer $mailer;

    /**
     * Create a new SMTP2GO transport instance.
     */
    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
        $this->mailer = new SMTP2GOMailer($apiKey);
    }

    /**
     * Send the message via SMTP2GO API.
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        try {
            // Reset the mailer for each new message
            $this->mailer = new SMTP2GOMailer($this->apiKey);

            // Set sender
            $from = $email->getFrom()[0];
            $this->mailer->setFrom($from->getAddress(), $from->getName() ?: '');

            // Set recipients
            foreach ($email->getTo() as $recipient) {
                $this->mailer->addTo($recipient->getAddress(), $recipient->getName() ?: '');
            }

            // Add CC recipients if any
            if ($cc = $email->getCc()) {
                foreach ($cc as $recipient) {
                    $this->mailer->addCc($recipient->getAddress(), $recipient->getName() ?: '');
                }
            }

            // Add BCC recipients if any
            if ($bcc = $email->getBcc()) {
                foreach ($bcc as $recipient) {
                    $this->mailer->addBcc($recipient->getAddress(), $recipient->getName() ?: '');
                }
            }

            // Set reply-to if present
            if ($replyTo = $email->getReplyTo()) {
                $this->mailer->setReplyTo($replyTo[0]->getAddress(), $replyTo[0]->getName() ?: '');
            }

            // Set subject
            $this->mailer->setSubject($email->getSubject());

            // Set email body
            $htmlBody = $email->getHtmlBody();
            $textBody = $email->getTextBody();

            if ($htmlBody && $textBody) {
                // Both HTML and text versions
                $this->mailer->setHtmlBody($htmlBody);
                $this->mailer->setTextBody($textBody);
            } elseif ($htmlBody) {
                // HTML only
                $this->mailer->setHtmlBody($htmlBody);
            } elseif ($textBody) {
                // Text only
                $this->mailer->setTextBody($textBody);
            }

            // Handle attachments
            foreach ($email->getAttachments() as $attachment) {
                $this->mailer->addAttachment(
                    $attachment->getBody(),
                    $attachment->getFilename(),
                    $attachment->getMediaType().'/'.$attachment->getMediaSubtype()
                );
            }

            // Add custom headers
            $customHeaders = [];
            foreach ($email->getHeaders()->all() as $header) {
                $name = $header->getName();
                // Skip standard headers that are handled separately
                if (! in_array(strtolower($name), ['from', 'to', 'cc', 'bcc', 'subject', 'reply-to', 'content-type', 'mime-version'])) {
                    $customHeaders[$name] = $header->getBodyAsString();
                }
            }

            if (! empty($customHeaders)) {
                $this->mailer->setCustomHeaders($customHeaders);
            }

            // Send the email
            $response = $this->mailer->send();

            // Check if successful
            if (isset($response['data']['succeeded']) && $response['data']['succeeded'] > 0) {
                Log::info('Email sent via SMTP2GO', [
                    'email_id' => $response['data']['email_id'] ?? null,
                    'request_id' => $response['request_id'] ?? null,
                    'succeeded' => $response['data']['succeeded'],
                ]);
            } else {
                $error = $response['data']['error'] ?? 'Unknown error occurred';
                Log::error('SMTP2GO send failed', [
                    'error' => $error,
                    'response' => $response,
                ]);
                throw new \Exception("SMTP2GO API Error: {$error}");
            }
        } catch (\Exception $e) {
            Log::error('SMTP2GO transport error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get string representation of the transport.
     */
    public function __toString(): string
    {
        return 'smtp2go';
    }
}
