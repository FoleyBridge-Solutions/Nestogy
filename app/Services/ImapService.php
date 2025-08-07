<?php

namespace App\Services;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Illuminate\Support\Collection;

class ImapService
{
    protected ClientManager $clientManager;
    protected array $config;
    protected ?Client $client = null;

    public function __construct(ClientManager $clientManager, array $config)
    {
        $this->clientManager = $clientManager;
        $this->config = $config;
    }

    /**
     * Connect to IMAP server
     */
    public function connect(string $account = 'default'): bool
    {
        try {
            $this->client = $this->clientManager->account($account);
            $this->client->connect();
            return true;
        } catch (\Exception $e) {
            logger()->error('IMAP connection failed', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Disconnect from IMAP server
     */
    public function disconnect(): void
    {
        if ($this->client) {
            $this->client->disconnect();
            $this->client = null;
        }
    }

    /**
     * Get all folders
     */
    public function getFolders(): Collection
    {
        if (!$this->client) {
            throw new \Exception('IMAP client not connected');
        }

        return $this->client->getFolders();
    }

    /**
     * Get folder by name
     */
    public function getFolder(string $name): ?Folder
    {
        if (!$this->client) {
            throw new \Exception('IMAP client not connected');
        }

        return $this->client->getFolder($name);
    }

    /**
     * Get messages from folder
     */
    public function getMessages(string $folderName = 'INBOX', int $limit = 50): Collection
    {
        $folder = $this->getFolder($folderName);
        
        if (!$folder) {
            return collect();
        }

        return $folder->messages()->limit($limit)->get();
    }

    /**
     * Get unread messages
     */
    public function getUnreadMessages(string $folderName = 'INBOX', int $limit = 50): Collection
    {
        $folder = $this->getFolder($folderName);
        
        if (!$folder) {
            return collect();
        }

        return $folder->messages()->unseen()->limit($limit)->get();
    }

    /**
     * Search messages
     */
    public function searchMessages(array $criteria, string $folderName = 'INBOX'): Collection
    {
        $folder = $this->getFolder($folderName);
        
        if (!$folder) {
            return collect();
        }

        $query = $folder->messages();

        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'from':
                    $query->from($value);
                    break;
                case 'to':
                    $query->to($value);
                    break;
                case 'subject':
                    $query->subject($value);
                    break;
                case 'body':
                    $query->body($value);
                    break;
                case 'since':
                    $query->since($value);
                    break;
                case 'before':
                    $query->before($value);
                    break;
                case 'seen':
                    if ($value) {
                        $query->seen();
                    } else {
                        $query->unseen();
                    }
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Message $message): bool
    {
        try {
            $message->setFlag('Seen');
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to mark message as read', [
                'message_id' => $message->getMessageId(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark message as unread
     */
    public function markAsUnread(Message $message): bool
    {
        try {
            $message->unsetFlag('Seen');
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to mark message as unread', [
                'message_id' => $message->getMessageId(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Move message to folder
     */
    public function moveMessage(Message $message, string $folderName): bool
    {
        try {
            $message->move($folderName);
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to move message', [
                'message_id' => $message->getMessageId(),
                'folder' => $folderName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete message
     */
    public function deleteMessage(Message $message): bool
    {
        try {
            $message->delete();
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to delete message', [
                'message_id' => $message->getMessageId(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get message attachments
     */
    public function getAttachments(Message $message): Collection
    {
        return $message->getAttachments();
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($attachment, string $path): bool
    {
        try {
            $attachment->save($path);
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to download attachment', [
                'attachment' => $attachment->getName(),
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process incoming emails for ticket creation
     */
    public function processIncomingEmails(): array
    {
        $processed = [];
        
        try {
            $messages = $this->getUnreadMessages();
            
            foreach ($messages as $message) {
                $processed[] = $this->processEmailForTicket($message);
                $this->markAsRead($message);
            }
        } catch (\Exception $e) {
            logger()->error('Failed to process incoming emails', [
                'error' => $e->getMessage(),
            ]);
        }

        return $processed;
    }

    /**
     * Process individual email for ticket creation
     */
    protected function processEmailForTicket(Message $message): array
    {
        return [
            'message_id' => $message->getMessageId(),
            'from' => $message->getFrom()->first()->mail ?? '',
            'from_name' => $message->getFrom()->first()->personal ?? '',
            'subject' => $message->getSubject(),
            'body' => $message->getHTMLBody() ?: $message->getTextBody(),
            'date' => $message->getDate(),
            'attachments' => $this->getAttachments($message)->count(),
            'processed_at' => now(),
        ];
    }

    /**
     * Test IMAP connection
     */
    public function testConnection(string $account = 'default'): bool
    {
        try {
            $client = $this->clientManager->account($account);
            $client->connect();
            $folders = $client->getFolders();
            $client->disconnect();
            
            return $folders->count() > 0;
        } catch (\Exception $e) {
            logger()->error('IMAP connection test failed', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}