<?php

namespace App\Domains\Email\Traits;

trait HasEmailStatusOperations
{
    public function markAsRead(): self
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true]);
            $this->emailFolder->decrement('unread_count');
        }

        return $this;
    }

    public function markAsUnread(): self
    {
        if ($this->is_read) {
            $this->update(['is_read' => false]);
            $this->emailFolder->increment('unread_count');
        }

        return $this;
    }

    public function flag(): self
    {
        $this->update(['is_flagged' => true]);

        return $this;
    }

    public function unflag(): self
    {
        $this->update(['is_flagged' => false]);

        return $this;
    }
}
