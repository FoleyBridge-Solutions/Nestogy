<?php

namespace App\Domains\Email\Controllers;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailMessage;
use App\Domains\Email\Services\EmailService;
use App\Domains\Email\Services\ImapService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    public function __construct(
        private EmailService $emailService,
        private ImapService $imapService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $accounts = EmailAccount::forUser($user->id)
            ->active()
            ->with('folders')
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('email.accounts.create')
                ->with('info', 'Please add an email account to get started.');
        }

        $selectedAccount = $this->getSelectedAccount($request, $accounts);
        $selectedFolder = $this->getSelectedFolder($request, $selectedAccount);
        $messagesQuery = $selectedFolder ? $selectedFolder->messages() : $selectedAccount->messages();
        
        $this->applyMessageFilters($request, $messagesQuery);
        
        $messages = $messagesQuery
            ->notDeleted()
            ->with(['attachments'])
            ->orderBy('sent_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $selectedMessage = $this->getSelectedMessage($request, $messages);
        $folderStats = $this->buildFolderStats($selectedAccount);

        return view('email.inbox.index', compact(
            'accounts',
            'selectedAccount',
            'selectedFolder',
            'messages',
            'selectedMessage',
            'folderStats'
        ));
    }

    private function getSelectedAccount(Request $request, $accounts)
    {
        $selectedAccountId = $request->get('account_id', $accounts->first()->id);
        $selectedAccount = $accounts->find($selectedAccountId);

        return $selectedAccount ?: $accounts->first();
    }

    private function getSelectedFolder(Request $request, $selectedAccount)
    {
        $selectedFolderId = $request->get('folder_id');
        
        if ($selectedFolderId) {
            $selectedFolder = $selectedAccount->folders()->find($selectedFolderId);
            if ($selectedFolder) {
                return $selectedFolder;
            }
        }

        return $selectedAccount->folders()
            ->where('type', 'inbox')
            ->first() ?: $selectedAccount->folders()->first();
    }

    private function applyMessageFilters(Request $request, $messagesQuery): void
    {
        if ($request->filled('search')) {
            $messagesQuery->search($request->search);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'unread' => $messagesQuery->unread(),
                'read' => $messagesQuery->read(),
                'flagged' => $messagesQuery->flagged(),
                'attachments' => $messagesQuery->withAttachments(),
                default => null
            };
        }

        if ($request->filled('from_date')) {
            $messagesQuery->fromDate($request->from_date);
        }

        if ($request->filled('to_date')) {
            $messagesQuery->toDate($request->to_date);
        }

        if ($request->filled('sender')) {
            $messagesQuery->fromSender($request->sender);
        }
    }

    private function getSelectedMessage(Request $request, $messages)
    {
        $selectedMessageId = $request->get('message_id');

        if (! $selectedMessageId) {
            return null;
        }

        $selectedMessage = $messages->where('id', $selectedMessageId)->first();

        if ($selectedMessage && ! $selectedMessage->is_read) {
            $this->emailService->markAsRead($selectedMessage);
        }

        return $selectedMessage;
    }

    private function buildFolderStats($selectedAccount)
    {
        return $selectedAccount->folders->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->getDisplayName(),
                'type' => $folder->type,
                'unread_count' => $folder->unread_count,
                'total_count' => $folder->message_count,
                'icon' => $folder->getIcon(),
            ];
        });
    }

    public function show(EmailMessage $message)
    {
        $this->authorize('view', $message);

        // Mark as read
        if (! $message->is_read) {
            $this->emailService->markAsRead($message);
        }

        $message->load(['attachments', 'replies', 'replyToMessage']);

        // Get thread messages if part of a thread
        $threadMessages = $message->thread_id ?
            $message->getThreadMessages() :
            collect([$message]);

        return view('email.inbox.show', compact('message', 'threadMessages'));
    }

    public function markAsRead(Request $request)
    {
        $messageIds = $request->input('message_ids', []);

        if (empty($messageIds)) {
            return response()->json(['success' => false, 'message' => 'No messages selected']);
        }

        $user = Auth::user();
        $messages = EmailMessage::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            $this->emailService->markAsRead($message);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
            'count' => $messages->count(),
        ]);
    }

    public function markAsUnread(Request $request)
    {
        $messageIds = $request->input('message_ids', []);

        if (empty($messageIds)) {
            return response()->json(['success' => false, 'message' => 'No messages selected']);
        }

        $user = Auth::user();
        $messages = EmailMessage::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            $this->emailService->markAsUnread($message);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as unread',
            'count' => $messages->count(),
        ]);
    }

    public function flag(Request $request)
    {
        $messageIds = $request->input('message_ids', []);

        if (empty($messageIds)) {
            return response()->json(['success' => false, 'message' => 'No messages selected']);
        }

        $user = Auth::user();
        $messages = EmailMessage::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            $message->flag();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages flagged',
            'count' => $messages->count(),
        ]);
    }

    public function unflag(Request $request)
    {
        $messageIds = $request->input('message_ids', []);

        if (empty($messageIds)) {
            return response()->json(['success' => false, 'message' => 'No messages selected']);
        }

        $user = Auth::user();
        $messages = EmailMessage::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            $message->unflag();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages unflagged',
            'count' => $messages->count(),
        ]);
    }

    public function delete(Request $request)
    {
        $messageIds = $request->input('message_ids', []);

        if (empty($messageIds)) {
            return response()->json(['success' => false, 'message' => 'No messages selected']);
        }

        $user = Auth::user();
        $messages = EmailMessage::whereHas('emailAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            $this->emailService->deleteEmail($message);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages deleted',
            'count' => $messages->count(),
        ]);
    }

    public function refresh(Request $request)
    {
        $accountId = $request->input('account_id');

        if (! $accountId) {
            return response()->json(['success' => false, 'message' => 'Account ID required']);
        }

        $account = EmailAccount::forUser(Auth::id())->findOrFail($accountId);

        try {
            $result = $this->imapService->syncAccount($account);

            return response()->json([
                'success' => true,
                'message' => "Refreshed! {$result['messages_synced']} new messages found.",
                'messages_synced' => $result['messages_synced'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function stats(Request $request)
    {
        $accountId = $request->input('account_id');
        $account = EmailAccount::forUser(Auth::id())->findOrFail($accountId);

        $stats = [
            'total_messages' => $account->messages()->count(),
            'unread_messages' => $account->messages()->unread()->count(),
            'flagged_messages' => $account->messages()->flagged()->count(),
            'messages_with_attachments' => $account->messages()->withAttachments()->count(),
            'last_synced' => $account->last_synced_at?->diffForHumans(),
            'sync_error' => $account->sync_error,
        ];

        return response()->json($stats);
    }
}
