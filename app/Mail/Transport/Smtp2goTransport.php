<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Log;
use SMTP2GO\ApiClient;
use SMTP2GO\Collections\Mail\AddressCollection;
use SMTP2GO\Collections\Mail\AttachmentCollection;
use SMTP2GO\Service\Mail\Send as MailSend;
use SMTP2GO\Types\Mail\Address;
use SMTP2GO\Types\Mail\Attachment;
use SMTP2GO\Types\Mail\CustomHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class Smtp2goTransport extends AbstractTransport
{
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        try {
            // Build sender
            $from = $email->getFrom()[0];
            $sender = new Address($from->getAddress(), $from->getName() ?: '');

            // Build recipients
            $toAddresses = [];
            foreach ($email->getTo() as $recipient) {
                $toAddresses[] = new Address($recipient->getAddress(), $recipient->getName() ?: '');
            }
            $recipients = new AddressCollection($toAddresses);

            // Get subject and body
            $subject = $email->getSubject() ?: '';
            $htmlBody = $email->getHtmlBody();
            $textBody = $email->getTextBody();
            
            // Use HTML or text body
            $body = $htmlBody ?: $textBody ?: '';

            // Create mail service
            $sendService = new MailSend($sender, $recipients, $subject, $body);

            // Add text body if both exist
            if ($htmlBody && $textBody) {
                $sendService->setTextBody($textBody);
            }

            // Add CC recipients
            if ($cc = $email->getCc()) {
                foreach ($cc as $recipient) {
                    $sendService->addAddress('cc', new Address($recipient->getAddress(), $recipient->getName() ?: ''));
                }
            }

            // Add BCC recipients
            if ($bcc = $email->getBcc()) {
                foreach ($bcc as $recipient) {
                    $sendService->addAddress('bcc', new Address($recipient->getAddress(), $recipient->getName() ?: ''));
                }
            }

            // Set reply-to if present
            if ($replyTo = $email->getReplyTo()) {
                $sendService->addCustomHeader(
                    new CustomHeader('Reply-To', $replyTo[0]->getAddress())
                );
            }

            // Handle attachments
            $attachments = [];
            foreach ($email->getAttachments() as $attachment) {
                $attachments[] = new Attachment(
                    $attachment->getFilename(),
                    $attachment->getBody(),
                    $attachment->getMediaType().'/'.$attachment->getMediaSubtype()
                );
            }
            if (!empty($attachments)) {
                $sendService->setAttachments(new AttachmentCollection($attachments));
            }

            // Create API client and send
            $apiClient = new ApiClient($this->apiKey);
            $success = $apiClient->consume($sendService);

            if ($success) {
                $responseBody = $apiClient->getResponseBody();
                Log::info('Email sent via SMTP2GO', [
                    'response' => $responseBody,
                ]);
            } else {
                $responseBody = $apiClient->getResponseBody();
                $error = $responseBody['data']['error'] ?? 'Unknown error occurred';
                Log::error('SMTP2GO send failed', [
                    'error' => $error,
                    'response' => $responseBody,
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

    public function __toString(): string
    {
        return 'smtp2go';
    }
}
