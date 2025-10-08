<?php

namespace App\Livewire\Email;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailFolder;
use App\Domains\Email\Models\EmailMessage;
use App\Domains\Email\Services\EmailService;
use App\Domains\Email\Services\ImapService;
use App\Livewire\Email\Concerns\ManagesMessageSelection;
use App\Livewire\Email\Concerns\PerformsBulkMessageActions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Inbox extends Component
{
    use WithPagination;
    use ManagesMessageSelection;
    use PerformsBulkMessageActions;

    // Queryable state
    public $accountId;

    public $folderId;

    public $messageId;

    public $search = '';

    public $status = '';

    public $fromDate = '';

    public $toDate = '';

    public $sender = '';

    public $sortBy = 'sent_at';

    public $sortDirection = 'desc';

    protected $queryString = [
        'accountId' => ['as' => 'account_id', 'except' => null],
        'folderId' => ['as' => 'folder_id', 'except' => null],
        'messageId' => ['as' => 'message_id', 'except' => null],
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'fromDate' => ['as' => 'from_date', 'except' => ''],
        'toDate' => ['as' => 'to_date', 'except' => ''],
        'sender' => ['except' => ''],
        'sortBy' => ['except' => 'sent_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount($accountId = null, $folderId = null, $messageId = null, $search = null)
    {
        $this->accountId = $accountId;
        $this->folderId = $folderId;
        $this->messageId = $messageId;
        if ($search !== null) {
            $this->search = $search;
        }

        // Default selections
        if (! $this->accountId && $this->accounts()->isNotEmpty()) {
            $this->accountId = $this->accounts()->first()->id;
        }
        if (! $this->folderId && $this->selectedAccount()) {
            $inbox = $this->selectedAccount()->folders()->where('type', 'inbox')->first();
            $this->folderId = $inbox?->id ?? $this->selectedAccount()->folders()->first()?->id;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingFromDate()
    {
        $this->resetPage();
    }

    public function updatingToDate()
    {
        $this->resetPage();
    }

    public function updatingSender()
    {
        $this->resetPage();
    }

    public function updatingAccountId()
    {
        $this->resetPage();
        $this->selected = [];
        $this->messageId = null;
    }

    public function updatingFolderId()
    {
        $this->resetPage();
        $this->selected = [];
        $this->messageId = null;
    }

    #[Computed]
    public function accounts()
    {
        return EmailAccount::forUser(Auth::id())->active()->with('folders')->get();
    }

    #[Computed]
    public function selectedAccount(): ?EmailAccount
    {
        if (! $this->accountId) {
            return null;
        }

        return $this->accounts()->firstWhere('id', $this->accountId) ?? EmailAccount::forUser(Auth::id())->find($this->accountId);
    }

    #[Computed]
    public function selectedFolder(): ?EmailFolder
    {
        if (! $this->selectedAccount()) {
            return null;
        }
        if (! $this->folderId) {
            return null;
        }

        return $this->selectedAccount()->folders->firstWhere('id', $this->folderId)
            ?: $this->selectedAccount()->folders()->find($this->folderId);
    }

    #[Computed]
    public function folderStats()
    {
        $account = $this->selectedAccount();
        if (! $account) {
            return collect();
        }

        return $account->folders->map(function ($folder) {
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

    #[Computed]
    public function messages()
    {
        $account = $this->selectedAccount();
        if (! $account) {
            return EmailMessage::whereRaw('1=0')->paginate(1);
        }

        $query = $this->selectedFolder()
            ? $this->selectedFolder()->messages()
            : $account->messages();

        if ($this->search) {
            $query->search($this->search);
        }
        if ($this->status) {
            match ($this->status) {
                'unread' => $query->unread(),
                'read' => $query->read(),
                'flagged' => $query->flagged(),
                'attachments' => $query->withAttachments(),
                default => null,
            };
        }
        if ($this->fromDate) {
            $query->fromDate($this->fromDate);
        }
        if ($this->toDate) {
            $query->toDate($this->toDate);
        }
        if ($this->sender) {
            $query->fromSender($this->sender);
        }

        $query->notDeleted()->with(['attachments'])->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(50);
    }

    #[Computed]
    public function selectedMessage(): ?EmailMessage
    {
        if (! $this->messageId) {
            return null;
        }
        $message = $this->messages()->getCollection()->firstWhere('id', (int) $this->messageId)
            ?: EmailMessage::with('attachments')->find($this->messageId);

        return $message;
    }

    public function selectMessage($id)
    {
        $this->messageId = $id;
        $message = EmailMessage::find($id);
        if ($message && ! $message->is_read) {
            app(EmailService::class)->markAsRead($message);
            // Refresh stats and list
            $this->dispatch('message-read');
        }
    }

    public function refreshInbox()
    {
        $account = $this->selectedAccount();
        if (! $account) {
            return;
        }
        try {
            $result = app(ImapService::class)->syncAccount($account);
            Flux::toast('Refreshed: '.($result['messages_synced'] ?? 0).' new messages.');
        } catch (\Exception $e) {
            Flux::toast('Refresh failed: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.email.inbox');
    }
}
