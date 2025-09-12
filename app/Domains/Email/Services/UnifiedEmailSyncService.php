<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Services\ImapService;
use App\Domains\Email\Services\Providers\MicrosoftGraphProvider;
use App\Domains\Email\Services\Providers\GoogleWorkspaceProvider;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UnifiedEmailSyncService
{
    protected ImapService $imapService;
    protected EmailProviderService $providerService;
    protected OAuthTokenManager $tokenManager;

    public function __construct(
        ImapService $imapService,
        EmailProviderService $providerService,
        OAuthTokenManager $tokenManager
    ) {
        $this->imapService = $imapService;
        $this->providerService = $providerService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Sync an email account using the appropriate method
     */
    public function syncAccount(EmailAccount $account): array
    {
        try {
            if ($account->connection_type === 'oauth') {
                return $this->syncOAuthAccount($account);
            } else {
                return $this->syncImapAccount($account);
            }
        } catch (\Exception $e) {
            Log::error('Email sync failed', [
                'account_id' => $account->id,
                'connection_type' => $account->connection_type,
                'error' => $e->getMessage(),
            ]);

            $account->update([
                'sync_error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'folders_synced' => 0,
                'messages_synced' => 0,
            ];
        }
    }

    /**
     * Sync OAuth-based email account
     */
    protected function syncOAuthAccount(EmailAccount $account): array
    {
        // Ensure tokens are valid
        if (!$this->tokenManager->ensureValidTokens($account)) {
            throw new \Exception('Unable to refresh OAuth tokens');
        }

        // Use provider-specific sync method
        switch ($account->oauth_provider) {
            case 'microsoft365':
                return $this->syncMicrosoft365Account($account);
            case 'google_workspace':
                return $this->syncGoogleWorkspaceAccount($account);
            default:
                throw new \Exception("Unsupported OAuth provider: {$account->oauth_provider}");
        }
    }

    /**
     * Sync Microsoft 365 account using Graph API
     */
    protected function syncMicrosoft365Account(EmailAccount $account): array
    {
        $results = [
            'success' => true,
            'folders_synced' => 0,
            'messages_synced' => 0,
            'errors' => [],
        ];

        try {
            $provider = new MicrosoftGraphProvider($account->company);

            // Get access token
            $accessToken = $this->tokenManager->getValidAccessToken($account);
            if (!$accessToken) {
                throw new \Exception('No valid access token available');
            }

            // Sync folders
            $foldersResult = $this->syncMicrosoftFolders($account, $accessToken);
            $results['folders_synced'] = $foldersResult['count'];

            // Sync messages for each folder
            foreach ($account->folders as $folder) {
                try {
                    $messagesResult = $this->syncMicrosoftMessages($account, $folder, $accessToken);
                    $results['messages_synced'] += $messagesResult['count'];
                } catch (\Exception $e) {
                    $results['errors'][] = "Folder {$folder->name}: " . $e->getMessage();
                }
            }

            $account->update([
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Sync Google Workspace account using Gmail API
     */
    protected function syncGoogleWorkspaceAccount(EmailAccount $account): array
    {
        $results = [
            'success' => true,
            'folders_synced' => 0,
            'messages_synced' => 0,
            'errors' => [],
        ];

        try {
            $provider = new GoogleWorkspaceProvider($account->company);

            // Get access token
            $accessToken = $this->tokenManager->getValidAccessToken($account);
            if (!$accessToken) {
                throw new \Exception('No valid access token available');
            }

            // Sync labels (Gmail's version of folders)
            $labelsResult = $this->syncGoogleLabels($account, $accessToken);
            $results['folders_synced'] = $labelsResult['count'];

            // Sync messages
            $messagesResult = $this->syncGoogleMessages($account, $accessToken);
            $results['messages_synced'] = $messagesResult['count'];

            $account->update([
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Sync traditional IMAP account
     */
    protected function syncImapAccount(EmailAccount $account): array
    {
        return $this->imapService->syncAccount($account);
    }

    /**
     * Sync Microsoft 365 folders using Graph API
     */
    protected function syncMicrosoftFolders(EmailAccount $account, string $accessToken): array
    {
        // Implementation would use Microsoft Graph API to sync folders
        // This is a placeholder for the actual implementation
        return ['count' => 0];
    }

    /**
     * Sync Microsoft 365 messages using Graph API
     */
    protected function syncMicrosoftMessages(EmailAccount $account, $folder, string $accessToken): array
    {
        // Implementation would use Microsoft Graph API to sync messages
        // This is a placeholder for the actual implementation
        return ['count' => 0];
    }

    /**
     * Sync Google Workspace labels using Gmail API
     */
    protected function syncGoogleLabels(EmailAccount $account, string $accessToken): array
    {
        try {
            $provider = new GoogleWorkspaceProvider($account->company);
            $labels = $provider->getLabels($accessToken);
            
            $syncedCount = 0;
            foreach ($labels as $label) {
                // Skip system labels that aren't useful as folders
                if (in_array($label['type'] ?? '', ['system']) && 
                    !in_array($label['id'], ['INBOX', 'SENT', 'DRAFT', 'SPAM', 'TRASH'])) {
                    continue;
                }
                
                // Create or update email folder
                \App\Domains\Email\Models\EmailFolder::updateOrCreate(
                    [
                        'email_account_id' => $account->id,
                        'remote_id' => $label['id'],
                    ],
                    [
                        'name' => $label['name'],
                        'type' => $this->mapGoogleLabelToType($label),
                        'is_selectable' => true,
                        'message_count' => $label['messagesTotal'] ?? 0,
                        'unread_count' => $label['messagesUnread'] ?? 0,
                    ]
                );
                $syncedCount++;
            }
            
            return ['count' => $syncedCount];
            
        } catch (\Exception $e) {
            Log::error('Failed to sync Google labels', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync Google Workspace messages using Gmail API
     */
    protected function syncGoogleMessages(EmailAccount $account, string $accessToken): array
    {
        try {
            $provider = new GoogleWorkspaceProvider($account->company);
            
            // Get recent messages (last 7 days by default)
            $query = 'newer_than:7d';
            $messages = $provider->getMessages($accessToken, [
                'query' => $query,
                'maxResults' => 50
            ]);
            
            $syncedCount = 0;
            if (!empty($messages['messages'])) {
                foreach ($messages['messages'] as $messageRef) {
                    try {
                        // Get full message details
                        $messageDetails = $provider->getMessage($accessToken, $messageRef['id']);
                        
                        // Extract message data
                        $messageData = $this->parseGoogleMessage($messageDetails);
                        
                        // Create or update email message
                        \App\Domains\Email\Models\EmailMessage::updateOrCreate(
                            [
                                'email_account_id' => $account->id,
                                'remote_id' => $messageDetails['id'],
                            ],
                            $messageData
                        );
                        
                        $syncedCount++;
                        
                    } catch (\Exception $e) {
                        Log::warning('Failed to sync individual Gmail message', [
                            'account_id' => $account->id,
                            'message_id' => $messageRef['id'],
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with other messages
                    }
                }
            }
            
            return ['count' => $syncedCount];
            
        } catch (\Exception $e) {
            Log::error('Failed to sync Google messages', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Map Google label to folder type
     */
    protected function mapGoogleLabelToType(array $label): string
    {
        $labelId = $label['id'] ?? '';
        $labelName = strtolower($label['name'] ?? '');
        
        if ($labelId === 'INBOX') return 'inbox';
        if ($labelId === 'SENT') return 'sent';
        if ($labelId === 'DRAFT') return 'drafts';
        if ($labelId === 'SPAM') return 'spam';
        if ($labelId === 'TRASH') return 'trash';
        
        return 'folder';
    }

    /**
     * Parse Google message into database format
     */
    protected function parseGoogleMessage(array $messageDetails): array
    {
        $payload = $messageDetails['payload'] ?? [];
        $headers = $payload['headers'] ?? [];
        
        // Extract headers
        $subject = '';
        $from = '';
        $to = '';
        $date = '';
        
        foreach ($headers as $header) {
            switch (strtolower($header['name'])) {
                case 'subject':
                    $subject = $header['value'];
                    break;
                case 'from':
                    $from = $header['value'];
                    break;
                case 'to':
                    $to = $header['value'];
                    break;
                case 'date':
                    $date = $header['value'];
                    break;
            }
        }
        
        // Extract body
        $body = $this->extractGoogleMessageBody($payload);
        
        return [
            'subject' => $subject,
            'from_address' => $from,
            'to_address' => $to,
            'body' => $body,
            'received_at' => $date ? Carbon::parse($date) : now(),
            'is_read' => !in_array('UNREAD', $messageDetails['labelIds'] ?? []),
            'size' => $messageDetails['sizeEstimate'] ?? 0,
        ];
    }

    /**
     * Extract body from Google message payload
     */
    protected function extractGoogleMessageBody(array $payload): string
    {
        if (!empty($payload['body']['data'])) {
            return base64_decode(str_replace(['-', '_'], ['+', '/'], $payload['body']['data']));
        }
        
        if (!empty($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/plain' && !empty($part['body']['data'])) {
                    return base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                }
            }
            
            // Fallback to HTML if no plain text found
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/html' && !empty($part['body']['data'])) {
                    return base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                }
            }
        }
        
        return '';
    }
}