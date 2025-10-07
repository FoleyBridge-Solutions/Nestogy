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
            $this->sendEmail($sendService);
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
        $sender = $this->buildSender($email);
        $recipients = $this->buildRecipients($email);
        $subject = $email->getSubject() ?: '';
        $body = $this->buildBody($email);

        $sendService = new MailSend($sender, $recipients, $subject, $body);

        $this->addTextBodyIfNeeded($sendService, $email);
        $this->addCcRecipients($sendService, $email);
        $this->addBccRecipients($sendService, $email);
        $this->addReplyTo($sendService, $email);
        $this->addAttachments($sendService, $email);

        return $sendService;
    }

    protected function buildSender($email): Address
    {
        $from = $email->getFrom()[0];
        return new Address($from->getAddress(), $from->getName() ?: '');
    }

    protected function buildRecipients($email): AddressCollection
    {
        $toAddresses = [];
        foreach ($email->getTo() as $recipient) {
            $toAddresses[] = new Address($recipient->getAddress(), $recipient->getName() ?: '');
        }
        return new AddressCollection($toAddresses);
    }

    protected function buildBody($email): string
    {
        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();
        return $htmlBody ?: $textBody ?: '';
    }

    protected function addTextBodyIfNeeded(MailSend $sendService, $email): void
    {
        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();
        
        if ($htmlBody && $textBody) {
            $sendService->setTextBody($textBody);
        }
    }

    protected function addCcRecipients(MailSend $sendService, $email): void
    {
        if ($cc = $email->getCc()) {
            foreach ($cc as $recipient) {
                $sendService->addAddress('cc', new Address($recipient->getAddress(), $recipient->getName() ?: ''));
            }
        }
    }

    protected function addBccRecipients(MailSend $sendService, $email): void
    {
        if ($bcc = $email->getBcc()) {
            foreach ($bcc as $recipient) {
                $sendService->addAddress('bcc', new Address($recipient->getAddress(), $recipient->getName() ?: ''));
            }
        }
    }

    protected function addReplyTo(MailSend $sendService, $email): void
    {
        if ($replyTo = $email->getReplyTo()) {
            $sendService->addCustomHeader(
                new CustomHeader('Reply-To', $replyTo[0]->getAddress())
            );
        }
    }

    protected function addAttachments(MailSend $sendService, $email): void
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

    protected function sendEmail(MailSend $sendService): void
    {
        $apiClient = new ApiClient($this->apiKey);
        $success = $apiClient->consume($sendService);

        if ($success) {
            $this->logSuccess($apiClient);
        } else {
            $this->handleFailure($apiClient);
        }
    }

    protected function logSuccess(ApiClient $apiClient): void
    {
        $responseBody = $apiClient->getResponseBody();
        Log::info('Email sent via SMTP2GO', [
            'response' => $responseBody,
        ]);
    }

    protected function handleFailure(ApiClient $apiClient): void
    {
        $responseBody = $apiClient->getResponseBody();
        $error = $responseBody['data']['error'] ?? 'Unknown error occurred';
        Log::error('SMTP2GO send failed', [
            'error' => $error,
            'response' => $responseBody,
        ]);
        throw new \Exception("SMTP2GO API Error: {$error}");
    }

    public function __toString(): string
    {
        return 'smtp2go';
    }
}
