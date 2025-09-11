<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailMessage;
use App\Domains\Email\Models\EmailSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;

class EmailService
{
    public function __construct(
        private ImapService $imapService
    ) {}

    public function sendEmail(array $data, EmailAccount $account): array
    {
        try {
            // Configure mailer for this account
            $this->configureMailer($account);

            // Prepare email data
            $to = $data['to'] ?? [];
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $subject = $data['subject'] ?? '';
            $body = $data['body'] ?? '';
            $attachments = $data['attachments'] ?? [];
            $signature = $data['signature_id'] ? 
                EmailSignature::find($data['signature_id']) : null;

            // Add signature if specified
            if ($signature) {
                $signatureContent = $signature->processVariables($data['signature_variables'] ?? []);
                $body .= "\n\n" . $signatureContent['html'];
            }

            // Create and send email
            Mail::send([], [], function ($message) use ($to, $cc, $bcc, $subject, $body, $attachments, $account) {
                $message->from($account->email_address, $account->user->name);
                
                foreach ((array) $to as $recipient) {
                    $message->to($recipient);
                }
                
                foreach ((array) $cc as $recipient) {
                    $message->cc($recipient);
                }
                
                foreach ((array) $bcc as $recipient) {
                    $message->bcc($recipient);
                }
                
                $message->subject($subject);
                $message->html($body);
                
                // Add attachments
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    }
                }
            });

            // Store sent email in database (optional)
            $this->storeSentEmail($account, $data);

            return [
                'success' => true,
                'message' => 'Email sent successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Email send failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function saveDraft(array $data, EmailAccount $account): EmailMessage
    {
        // Find sent folder or create it
        $draftsFolder = $account->folders()
            ->where('type', 'drafts')
            ->first();

        if (!$draftsFolder) {
            $draftsFolder = $account->folders()->create([
                'name' => 'Drafts',
                'path' => 'Drafts',
                'type' => 'drafts',
                'is_subscribed' => true,
                'is_selectable' => true,
            ]);
        }

        return EmailMessage::create([
            'email_account_id' => $account->id,
            'email_folder_id' => $draftsFolder->id,
            'message_id' => 'draft_' . uniqid(),
            'uid' => 'draft_' . uniqid(),
            'subject' => $data['subject'] ?? '',
            'from_address' => $account->email_address,
            'from_name' => $account->user->name,
            'to_addresses' => (array) ($data['to'] ?? []),
            'cc_addresses' => (array) ($data['cc'] ?? []),
            'bcc_addresses' => (array) ($data['bcc'] ?? []),
            'body_html' => $data['body'] ?? '',
            'body_text' => strip_tags($data['body'] ?? ''),
            'preview' => \Illuminate\Support\Str::limit(strip_tags($data['body'] ?? ''), 200),
            'sent_at' => null,
            'received_at' => now(),
            'is_draft' => true,
            'is_read' => true,
        ]);
    }

    public function forwardEmail(EmailMessage $originalMessage, array $data, EmailAccount $account): array
    {
        $forwardData = [
            'to' => $data['to'] ?? [],
            'cc' => $data['cc'] ?? [],
            'bcc' => $data['bcc'] ?? [],
            'subject' => 'Fwd: ' . $originalMessage->subject,
            'body' => $this->buildForwardBody($originalMessage, $data['body'] ?? ''),
            'attachments' => $data['attachments'] ?? [],
        ];

        $result = $this->sendEmail($forwardData, $account);

        if ($result['success']) {
            $originalMessage->update(['is_answered' => true]);
        }

        return $result;
    }

    public function replyToEmail(EmailMessage $originalMessage, array $data, EmailAccount $account): array
    {
        $replyData = [
            'to' => [$originalMessage->from_address],
            'cc' => $data['reply_all'] ? $originalMessage->cc_addresses : [],
            'subject' => 'Re: ' . preg_replace('/^re:\s*/i', '', $originalMessage->subject),
            'body' => $this->buildReplyBody($originalMessage, $data['body'] ?? ''),
            'attachments' => $data['attachments'] ?? [],
        ];

        $result = $this->sendEmail($replyData, $account);

        if ($result['success']) {
            $originalMessage->update(['is_answered' => true]);
        }

        return $result;
    }

    public function markAsRead(EmailMessage $message): void
    {
        $message->markAsRead();
        
        // If connected to IMAP, mark on server too
        try {
            $account = $message->emailAccount;
            $client = $this->imapService->createClient($account);
            $client->connect();
            
            $folder = $client->getFolderByPath($message->emailFolder->path);
            if ($folder) {
                $remoteMessage = $folder->getMessage($message->uid);
                if ($remoteMessage) {
                    $remoteMessage->setFlag('Seen');
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to mark message as read on server', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function markAsUnread(EmailMessage $message): void
    {
        $message->markAsUnread();
        
        // If connected to IMAP, mark on server too
        try {
            $account = $message->emailAccount;
            $client = $this->imapService->createClient($account);
            $client->connect();
            
            $folder = $client->getFolderByPath($message->emailFolder->path);
            if ($folder) {
                $remoteMessage = $folder->getMessage($message->uid);
                if ($remoteMessage) {
                    $remoteMessage->unsetFlag('Seen');
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to mark message as unread on server', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteEmail(EmailMessage $message): void
    {
        // Move to trash folder if it exists, otherwise just mark as deleted
        $account = $message->emailAccount;
        $trashFolder = $account->folders()->where('type', 'trash')->first();

        if ($trashFolder) {
            $message->update([
                'email_folder_id' => $trashFolder->id,
                'is_deleted' => true,
            ]);
        } else {
            $message->update(['is_deleted' => true]);
        }

        // Delete on IMAP server
        try {
            $client = $this->imapService->createClient($account);
            $client->connect();
            
            $folder = $client->getFolderByPath($message->emailFolder->path);
            if ($folder) {
                $remoteMessage = $folder->getMessage($message->uid);
                if ($remoteMessage) {
                    $remoteMessage->delete();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete message on server', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function configureMailer(EmailAccount $account): void
    {
        config([
            'mail.mailers.smtp.host' => $account->smtp_host,
            'mail.mailers.smtp.port' => $account->smtp_port,
            'mail.mailers.smtp.username' => $account->smtp_username,
            'mail.mailers.smtp.password' => $account->smtp_password,
            'mail.mailers.smtp.encryption' => $account->smtp_encryption,
            'mail.from.address' => $account->email_address,
            'mail.from.name' => $account->user->name,
        ]);
    }

    private function storeSentEmail(EmailAccount $account, array $data): void
    {
        // Find sent folder or create it
        $sentFolder = $account->folders()
            ->where('type', 'sent')
            ->first();

        if (!$sentFolder) {
            $sentFolder = $account->folders()->create([
                'name' => 'Sent',
                'path' => 'Sent',
                'type' => 'sent',
                'is_subscribed' => true,
                'is_selectable' => true,
            ]);
        }

        EmailMessage::create([
            'email_account_id' => $account->id,
            'email_folder_id' => $sentFolder->id,
            'message_id' => 'sent_' . uniqid(),
            'uid' => 'sent_' . uniqid(),
            'subject' => $data['subject'] ?? '',
            'from_address' => $account->email_address,
            'from_name' => $account->user->name,
            'to_addresses' => (array) ($data['to'] ?? []),
            'cc_addresses' => (array) ($data['cc'] ?? []),
            'bcc_addresses' => (array) ($data['bcc'] ?? []),
            'body_html' => $data['body'] ?? '',
            'body_text' => strip_tags($data['body'] ?? ''),
            'preview' => \Illuminate\Support\Str::limit(strip_tags($data['body'] ?? ''), 200),
            'sent_at' => now(),
            'received_at' => now(),
            'is_read' => true,
        ]);
    }

    private function buildReplyBody(EmailMessage $originalMessage, string $newBody): string
    {
        $replyHeader = "On {$originalMessage->sent_at->format('M j, Y')} at {$originalMessage->sent_at->format('g:i A')}, {$originalMessage->from_name} <{$originalMessage->from_address}> wrote:";
        
        $quotedBody = collect(explode("\n", strip_tags($originalMessage->body_text ?: $originalMessage->body_html)))
            ->map(fn($line) => '> ' . $line)
            ->implode("\n");

        return $newBody . "\n\n" . $replyHeader . "\n" . $quotedBody;
    }

    private function buildForwardBody(EmailMessage $originalMessage, string $newBody): string
    {
        $forwardHeader = "---------- Forwarded message ---------\n";
        $forwardHeader .= "From: {$originalMessage->from_name} <{$originalMessage->from_address}>\n";
        $forwardHeader .= "Date: {$originalMessage->sent_at->format('D, M j, Y \\a\\t g:i A')}\n";
        $forwardHeader .= "Subject: {$originalMessage->subject}\n";
        
        if ($originalMessage->to_addresses) {
            $forwardHeader .= "To: " . implode(', ', $originalMessage->to_addresses) . "\n";
        }
        
        $forwardHeader .= "\n";

        return $newBody . "\n\n" . $forwardHeader . ($originalMessage->body_text ?: strip_tags($originalMessage->body_html));
    }
}