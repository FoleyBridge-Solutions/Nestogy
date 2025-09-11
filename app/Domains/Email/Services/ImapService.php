<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailFolder;
use App\Domains\Email\Models\EmailMessage;
use App\Domains\Email\Models\EmailAttachment;
use App\Domains\Email\Services\CommunicationLogIntegrationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

class ImapService
{
    private ClientManager $clientManager;
    private CommunicationLogIntegrationService $communicationLogService;

    public function __construct(CommunicationLogIntegrationService $communicationLogService)
    {
        $this->clientManager = new ClientManager();
        $this->communicationLogService = $communicationLogService;
    }

    public function testConnection(EmailAccount $account): array
    {
        try {
            $client = $this->createClient($account);
            $client->connect();
            
            $folders = $client->getFolders();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'folder_count' => $folders->count(),
                'folders' => $folders->map(fn($folder) => $folder->name)->toArray(),
            ];
            
        } catch (\Exception $e) {
            Log::error('IMAP connection test failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function syncAccount(EmailAccount $account): array
    {
        $results = [
            'folders_synced' => 0,
            'messages_synced' => 0,
            'errors' => [],
        ];

        try {
            $client = $this->createClient($account);
            $client->connect();

            // Sync folders first
            $this->syncFolders($account, $client);
            $results['folders_synced'] = $account->folders()->count();

            // Sync messages for each folder
            foreach ($account->folders as $folder) {
                try {
                    $messageCount = $this->syncFolderMessages($account, $folder, $client);
                    $results['messages_synced'] += $messageCount;
                } catch (\Exception $e) {
                    $results['errors'][] = "Folder {$folder->name}: " . $e->getMessage();
                }
            }

            $account->update([
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Account sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $account->update([
                'sync_error' => $e->getMessage(),
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    public function syncFolders(EmailAccount $account, Client $client): void
    {
        $remoteFolders = $client->getFolders();
        $existingFolders = $account->folders()->pluck('path', 'path');

        foreach ($remoteFolders as $remoteFolder) {
            $folderPath = $remoteFolder->path;
            
            if (!isset($existingFolders[$folderPath])) {
                EmailFolder::create([
                    'email_account_id' => $account->id,
                    'name' => $remoteFolder->name,
                    'path' => $folderPath,
                    'type' => $this->determineFolderType($remoteFolder->name, $folderPath),
                    'message_count' => $remoteFolder->examine()['exists'] ?? 0,
                    'unread_count' => $remoteFolder->examine()['recent'] ?? 0,
                    'is_subscribed' => true,
                    'is_selectable' => $remoteFolder->hasChildren() === false,
                    'attributes' => $remoteFolder->getAttributes(),
                    'last_synced_at' => now(),
                ]);
            } else {
                // Update existing folder stats
                $folder = $account->folders()->where('path', $folderPath)->first();
                if ($folder) {
                    $stats = $remoteFolder->examine();
                    $folder->update([
                        'message_count' => $stats['exists'] ?? 0,
                        'last_synced_at' => now(),
                    ]);
                }
            }
        }
    }

    public function syncFolderMessages(EmailAccount $account, EmailFolder $folder, Client $client, int $limit = 100): int
    {
        $remoteFolder = $client->getFolderByPath($folder->path);
        if (!$remoteFolder) {
            throw new \Exception("Folder not found: {$folder->path}");
        }

        // Get messages from server (newest first)
        $messages = $remoteFolder->messages()
            ->setFetchOrder('desc')
            ->limit($limit, 1)
            ->get();

        $syncedCount = 0;
        $existingMessages = $folder->messages()->pluck('uid', 'uid');

        foreach ($messages as $message) {
            try {
                $uid = $message->getUid();
                
                // Skip if message already exists
                if (isset($existingMessages[$uid])) {
                    continue;
                }

                $this->storeMessage($account, $folder, $message);
                $syncedCount++;

            } catch (\Exception $e) {
                Log::warning('Failed to sync message', [
                    'account_id' => $account->id,
                    'folder_id' => $folder->id,
                    'message_uid' => $message->getUid() ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update folder unread count
        $folder->update([
            'unread_count' => $folder->messages()->where('is_read', false)->count(),
            'last_synced_at' => now(),
        ]);

        return $syncedCount;
    }

    public function storeMessage(EmailAccount $account, EmailFolder $folder, Message $message): EmailMessage
    {
        $messageId = $message->getMessageId();
        $uid = $message->getUid();
        
        // Determine thread ID (simplified - could be enhanced with proper threading)
        $threadId = $this->generateThreadId($message);

        // Process addresses
        $fromAddress = $message->getFrom()->first();
        $toAddresses = $message->getTo()->map(fn($addr) => $addr->mail)->toArray();
        $ccAddresses = $message->getCc()->map(fn($addr) => $addr->mail)->toArray();
        $bccAddresses = $message->getBcc()->map(fn($addr) => $addr->mail)->toArray();
        $replyToAddresses = $message->getReplyTo()->map(fn($addr) => $addr->mail)->toArray();

        // Create message record
        $emailMessage = EmailMessage::create([
            'email_account_id' => $account->id,
            'email_folder_id' => $folder->id,
            'message_id' => $messageId,
            'uid' => $uid,
            'thread_id' => $threadId,
            'subject' => $message->getSubject(),
            'from_address' => $fromAddress ? $fromAddress->mail : '',
            'from_name' => $fromAddress ? $fromAddress->personal : null,
            'to_addresses' => $toAddresses,
            'cc_addresses' => $ccAddresses,
            'bcc_addresses' => $bccAddresses,
            'reply_to_addresses' => $replyToAddresses,
            'body_text' => $message->getTextBody(),
            'body_html' => $message->getHTMLBody(),
            'preview' => $this->generatePreview($message),
            'sent_at' => $message->getDate(),
            'received_at' => now(),
            'size_bytes' => $message->getSize(),
            'priority' => $this->determinePriority($message),
            'is_read' => $message->hasFlag('Seen'),
            'is_flagged' => $message->hasFlag('Flagged'),
            'is_draft' => $message->hasFlag('Draft'),
            'is_answered' => $message->hasFlag('Answered'),
            'has_attachments' => $message->hasAttachments(),
            'headers' => $message->getHeader()->toArray(),
            'flags' => $message->getFlags(),
        ]);

        // Store attachments
        if ($message->hasAttachments()) {
            $this->storeAttachments($emailMessage, $message);
        }

        // Auto-process if enabled
        if ($this->communicationLogService->shouldCreateCommunicationLog($emailMessage)) {
            $this->communicationLogService->createCommunicationLogFromEmail($emailMessage);
        }

        if ($account->auto_create_tickets && $this->shouldCreateTicket($emailMessage)) {
            $this->createTicketFromEmail($emailMessage);
        }

        return $emailMessage;
    }

    public function storeAttachments(EmailMessage $emailMessage, Message $message): void
    {
        foreach ($message->getAttachments() as $attachment) {
            try {
                $filename = $attachment->getName() ?: 'attachment_' . uniqid();
                $contentType = $attachment->getContentType();
                $sizeBytes = $attachment->getSize();
                $content = $attachment->getContent();

                // Generate file hash for deduplication
                $hash = hash('sha256', $content);

                // Store file
                $storagePath = 'email_attachments/' . $emailMessage->id . '/' . $filename;
                Storage::disk('local')->put($storagePath, $content);

                // Create attachment record
                $emailAttachment = EmailAttachment::create([
                    'email_message_id' => $emailMessage->id,
                    'filename' => $filename,
                    'content_type' => $contentType,
                    'size_bytes' => $sizeBytes,
                    'content_id' => $attachment->getContentId(),
                    'is_inline' => $attachment->getDisposition() === 'inline',
                    'encoding' => $attachment->getEncoding(),
                    'disposition' => $attachment->getDisposition() ?: 'attachment',
                    'storage_disk' => 'local',
                    'storage_path' => $storagePath,
                    'hash' => $hash,
                    'is_image' => Str::startsWith($contentType, 'image/'),
                ]);

                // Generate thumbnail for images
                if ($emailAttachment->is_image) {
                    $this->generateThumbnail($emailAttachment);
                }

            } catch (\Exception $e) {
                Log::warning('Failed to store attachment', [
                    'message_id' => $emailMessage->id,
                    'attachment_name' => $attachment->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function createClient(EmailAccount $account): Client
    {
        $config = [
            'host' => $account->imap_host,
            'port' => $account->imap_port,
            'encryption' => $account->imap_encryption,
            'validate_cert' => $account->imap_validate_cert,
            'username' => $account->imap_username,
            'password' => $account->imap_password,
        ];

        // Handle OAuth if needed
        if ($account->isOAuthProvider()) {
            $config['authentication'] = 'oauth';
            // Additional OAuth configuration would go here
        }

        return $this->clientManager->make($config);
    }

    private function determineFolderType(string $name, string $path): string
    {
        $name = strtolower($name);
        $path = strtolower($path);

        return match (true) {
            str_contains($name, 'inbox') || $path === 'inbox' => 'inbox',
            str_contains($name, 'sent') => 'sent',
            str_contains($name, 'draft') => 'drafts',
            str_contains($name, 'trash') || str_contains($name, 'deleted') => 'trash',
            str_contains($name, 'spam') || str_contains($name, 'junk') => 'spam',
            default => 'custom'
        };
    }

    private function generateThreadId(Message $message): string
    {
        // Simple thread ID generation - could be enhanced with proper threading logic
        $subject = preg_replace('/^(re:|fwd?:|fw:)\s*/i', '', $message->getSubject() ?: '');
        return hash('md5', $subject . $message->getMessageId());
    }

    private function generatePreview(Message $message, int $length = 200): string
    {
        $text = $message->getTextBody() ?: strip_tags($message->getHTMLBody() ?: '');
        return Str::limit($text, $length);
    }

    private function determinePriority(Message $message): string
    {
        $priority = $message->getPriority();
        
        return match ($priority) {
            1, 2 => 'high',
            4, 5 => 'low',
            default => 'normal'
        };
    }

    private function generateThumbnail(EmailAttachment $attachment): void
    {
        // This would implement thumbnail generation for images
        // For now, just log that it should be implemented
        Log::info('Thumbnail generation needed', ['attachment_id' => $attachment->id]);
    }



    private function shouldCreateTicket(EmailMessage $emailMessage): bool
    {
        // Logic to determine if a ticket should be created
        // This is a simple implementation - could be enhanced with filters
        return $emailMessage->isFromClient() && 
               $emailMessage->emailFolder->type === 'inbox' &&
               !$emailMessage->is_answered;
    }

    private function createTicketFromEmail(EmailMessage $emailMessage): void
    {
        // This would create a ticket from the email
        // Will be implemented when we get to the email-to-ticket task
        Log::info('Ticket creation needed', ['message_id' => $emailMessage->id]);
    }
}