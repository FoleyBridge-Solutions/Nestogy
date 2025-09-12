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
        // Force error logging to ensure we see this in production
        error_log("OAUTH_DEBUG: Starting syncAccount for {$account->email_address} (ID: {$account->id})");
        error_log("OAUTH_DEBUG: Connection type: {$account->connection_type}, Provider: {$account->provider}");
        
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
        error_log("OAUTH_DEBUG: Starting syncOAuthAccount for provider: {$account->oauth_provider}");
        
        // Ensure tokens are valid
        if (!$this->tokenManager->ensureValidTokens($account)) {
            error_log("OAUTH_DEBUG: Token refresh failed for account {$account->id}");
            throw new \Exception('Unable to refresh OAuth tokens');
        }
        
        error_log("OAUTH_DEBUG: Tokens validated successfully for account {$account->id}");

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
            Log::info('Starting Google labels sync', [
                'account_id' => $account->id,
                'account_email' => $account->email_address,
            ]);

            $provider = new GoogleWorkspaceProvider($account->company);
            $labels = $provider->getLabels($accessToken);
            
            Log::info('Retrieved Gmail labels', [
                'account_id' => $account->id,
                'labels_count' => count($labels),
                'labels' => collect($labels)->pluck('name', 'id')->toArray(),
            ]);
            
            $syncedCount = 0;
            foreach ($labels as $label) {
                // Skip system labels that aren't useful as folders
                if (in_array($label['type'] ?? '', ['system']) && 
                    !in_array($label['id'], ['INBOX', 'SENT', 'DRAFT', 'SPAM', 'TRASH'])) {
                    Log::debug('Skipping system label', [
                        'label_id' => $label['id'],
                        'label_name' => $label['name'],
                        'label_type' => $label['type'] ?? 'unknown',
                    ]);
                    continue;
                }
                
                // Create or update email folder
                $folder = \App\Domains\Email\Models\EmailFolder::updateOrCreate(
                    [
                        'email_account_id' => $account->id,
                        'remote_id' => $label['id'],
                    ],
                    [
                        'name' => $label['name'],
                        'path' => $label['id'], // Gmail uses label ID as path
                        'type' => $this->mapGoogleLabelToType($label),
                        'is_selectable' => true,
                        'message_count' => $label['messagesTotal'] ?? 0,
                        'unread_count' => $label['messagesUnread'] ?? 0,
                        'last_synced_at' => now(),
                    ]
                );
                
                Log::debug('Synced Gmail label', [
                    'folder_id' => $folder->id,
                    'label_id' => $label['id'],
                    'label_name' => $label['name'],
                    'folder_type' => $folder->type,
                ]);
                
                $syncedCount++;
            }
            
            Log::info('Completed Google labels sync', [
                'account_id' => $account->id,
                'synced_count' => $syncedCount,
            ]);
            
            return ['count' => $syncedCount];
            
        } catch (\Exception $e) {
            Log::error('Failed to sync Google labels', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            Log::info('Starting Google messages sync', [
                'account_id' => $account->id,
                'account_email' => $account->email_address,
            ]);

            $provider = new GoogleWorkspaceProvider($account->company);
            
            // Get recent messages (last 7 days by default)
            $query = 'newer_than:7d';
            Log::info('Querying Gmail messages', [
                'account_id' => $account->id,
                'query' => $query,
                'max_results' => 50,
            ]);
            
            $messages = $provider->getMessages($accessToken, [
                'query' => $query,
                'maxResults' => 50
            ]);
            
            Log::info('Retrieved Gmail messages response', [
                'account_id' => $account->id,
                'messages_count' => count($messages['messages'] ?? []),
                'total_result_size_estimate' => $messages['resultSizeEstimate'] ?? 0,
                'has_next_page_token' => isset($messages['nextPageToken']),
            ]);
            
            $syncedCount = 0;
            if (!empty($messages['messages'])) {
                Log::info('Processing Gmail messages', [
                    'account_id' => $account->id,
                    'message_count' => count($messages['messages']),
                ]);
                foreach ($messages['messages'] as $messageRef) {
                    try {
                        // Get full message details
                        $messageDetails = $provider->getMessage($accessToken, $messageRef['id']);
                        
                        // Extract message data
                        $messageData = $this->parseGoogleMessage($messageDetails, $account);
                        
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
            } else {
                Log::warning('No Gmail messages found with current query, trying broader search', [
                    'account_id' => $account->id,
                    'original_query' => $query,
                ]);
                
                // Try with a broader query (last 30 days)
                $broadQuery = 'newer_than:30d';
                $broadMessages = $provider->getMessages($accessToken, [
                    'query' => $broadQuery,
                    'maxResults' => 10
                ]);
                
                Log::info('Broader Gmail search results', [
                    'account_id' => $account->id,
                    'query' => $broadQuery,
                    'messages_count' => count($broadMessages['messages'] ?? []),
                    'result_size_estimate' => $broadMessages['resultSizeEstimate'] ?? 0,
                ]);
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
    protected function parseGoogleMessage(array $messageDetails, \App\Domains\Email\Models\EmailAccount $account): array
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
        
        // Determine email folder
        $folderId = $this->determineGmailMessageFolder($messageDetails, $account);
        
        return [
            'subject' => $subject,
            'from_address' => $from,
            'to_addresses' => $to,
            'body_text' => $body,
            'body_html' => $body, // For now, same as text - can be improved later
            'received_at' => $date ? Carbon::parse($date) : now(),
            'is_read' => !in_array('UNREAD', $messageDetails['labelIds'] ?? []),
            'size_bytes' => $messageDetails['sizeEstimate'] ?? 0,
            'has_attachments' => false, // TODO: Detect attachments
            'is_draft' => in_array('DRAFT', $messageDetails['labelIds'] ?? []),
            'email_folder_id' => $folderId,
        ];
    }

    /**
     * Determine the appropriate folder for a Gmail message based on its labels
     */
    protected function determineGmailMessageFolder(array $messageDetails, \App\Domains\Email\Models\EmailAccount $account): int
    {
        $labelIds = $messageDetails['labelIds'] ?? [];
        
        Log::debug('Determining Gmail message folder', [
            'account_id' => $account->id,
            'message_id' => $messageDetails['id'] ?? 'unknown',
            'label_ids' => $labelIds,
        ]);
        
        // Priority mapping for Gmail labels to folder types
        $labelPriorities = [
            'DRAFT' => 'drafts',
            'SENT' => 'sent', 
            'SPAM' => 'spam',
            'TRASH' => 'trash',
            'INBOX' => 'inbox',
        ];
        
        // Find the highest priority label
        $targetFolderType = null;
        foreach ($labelPriorities as $labelId => $folderType) {
            if (in_array($labelId, $labelIds)) {
                $targetFolderType = $folderType;
                break;
            }
        }
        
        // If no standard labels found, look for custom labels
        if (!$targetFolderType && !empty($labelIds)) {
            // Try to find a custom folder that matches a label
            foreach ($labelIds as $labelId) {
                if (!in_array($labelId, ['UNREAD', 'IMPORTANT', 'CATEGORY_PERSONAL', 'CATEGORY_SOCIAL', 'CATEGORY_UPDATES', 'CATEGORY_FORUMS', 'CATEGORY_PROMOTIONS'])) {
                    $folder = \App\Domains\Email\Models\EmailFolder::where('email_account_id', $account->id)
                        ->where('remote_id', $labelId)
                        ->first();
                    
                    if ($folder) {
                        Log::debug('Found custom folder for Gmail message', [
                            'account_id' => $account->id,
                            'folder_id' => $folder->id,
                            'folder_name' => $folder->name,
                            'label_id' => $labelId,
                        ]);
                        return $folder->id;
                    }
                }
            }
        }
        
        // Default to inbox if no specific folder determined
        if (!$targetFolderType) {
            $targetFolderType = 'inbox';
        }
        
        // Find or create the folder
        $folder = \App\Domains\Email\Models\EmailFolder::where('email_account_id', $account->id)
            ->where('type', $targetFolderType)
            ->first();
            
        if (!$folder) {
            Log::info('Creating missing Gmail folder', [
                'account_id' => $account->id,
                'folder_type' => $targetFolderType,
            ]);
            
            $folderNames = [
                'inbox' => 'Inbox',
                'sent' => 'Sent',
                'drafts' => 'Drafts',
                'trash' => 'Trash',
                'spam' => 'Spam',
            ];
            
            $folder = \App\Domains\Email\Models\EmailFolder::create([
                'email_account_id' => $account->id,
                'name' => $folderNames[$targetFolderType] ?? ucfirst($targetFolderType),
                'remote_id' => strtoupper($targetFolderType),
                'type' => $targetFolderType,
                'is_selectable' => true,
                'message_count' => 0,
            ]);
        }
        
        Log::debug('Determined Gmail message folder', [
            'account_id' => $account->id,
            'folder_id' => $folder->id,
            'folder_name' => $folder->name,
            'folder_type' => $folder->type,
            'target_folder_type' => $targetFolderType,
        ]);
        
        return $folder->id;
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