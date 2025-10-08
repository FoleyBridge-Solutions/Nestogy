<?php

namespace App\Livewire\Email\Concerns;

use App\Domains\Email\Models\EmailMessage;
use App\Domains\Email\Services\EmailService;
use Flux\Flux;

trait PerformsBulkMessageActions
{
    public function bulkMarkRead()
    {
        $messages = EmailMessage::whereIn('id', $this->selected)->get();
        foreach ($messages as $m) {
            app(EmailService::class)->markAsRead($m);
        }
        $this->clearSelection();
        Flux::toast('Marked as read.');
    }

    public function bulkMarkUnread()
    {
        $messages = EmailMessage::whereIn('id', $this->selected)->get();
        foreach ($messages as $m) {
            app(EmailService::class)->markAsUnread($m);
        }
        $this->clearSelection();
        Flux::toast('Marked as unread.');
    }

    public function bulkFlag()
    {
        EmailMessage::whereIn('id', $this->selected)->get()->each->flag();
        $this->clearSelection();
        Flux::toast('Flagged messages.');
    }

    public function bulkUnflag()
    {
        EmailMessage::whereIn('id', $this->selected)->get()->each->unflag();
        $this->clearSelection();
        Flux::toast('Unflagged messages.');
    }

    public function bulkDelete()
    {
        $messages = EmailMessage::whereIn('id', $this->selected)->get();
        foreach ($messages as $m) {
            app(EmailService::class)->deleteEmail($m);
        }
        $this->clearSelection();
        Flux::toast('Moved to trash.', variant: 'warning');
    }
}
