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
            $sendService = $this->buildMailService($email);
            $this->addRecipients($email, $sendService);
            $this->addHeaders($email, $sendService);
            $this->addAttachments($email, $sendService);
            $this->sendViaApi($sendService);
        } catch (\Exception $e) {
            Log::error('SMTP2GO transport error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function buildMailService($email): MailSend
    {
        $from = $email->getFrom()[0];
        $sender = new Address($from->getAddress(), $from->getName() ?: '');

        $toAddresses = [];
        foreach ($email->getTo() as $recipient) {
            $toAddresses[] = new Address($recipient->getAddress(), $recipient->getName() ?: '');
        }
        $recipients = new AddressCollection($toAddresses);

        $subject = $email->getSubject() ?: '';
        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();
        $body = $htmlBody ?: $textBody ?: '';

        $sendService = new MailSend($sender, $recipients, $subject, $body);

        if ($htmlBody && $textBody) {
            $sendService->setTextBody($textBody);
        }

        return $sendService;
    }

    protected function addRecipients($email, MailSend $sendService): void
    {
        if ($cc = $email->getCc()) {
            foreach ($cc as $recipient) {
                $sendService->addAddress('cc', new Address($recipient->getAddress(), $recipient->getName() ?: ''));
            }
        }

        if ($bcc = $email->getBcc()) {
            foreach ($bcc as $recipient) {
                $sendService->addAddress('bcc', new Address($recipient->getAddress(), $recipient->getName() ?: ''));
            }
        }
    }

    protected function addHeaders($email, MailSend $sendService): void
    {
        if ($replyTo = $email->getReplyTo()) {
            $sendService->addCustomHeader(
                new CustomHeader('Reply-To', $replyTo[0]->getAddress())
            );
        }
    }

    protected function addAttachments($email, MailSend $sendService): void
    {
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
    }

    protected function sendViaApi(MailSend $sendService): void
    {
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
    }

    public function __toString(): string
    {
        return 'smtp2go';
    }
}
