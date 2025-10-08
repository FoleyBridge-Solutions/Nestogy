<?php

namespace App\Domains\Email\Traits;

trait HasEmailScopes
{
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeWithAttachments($query)
    {
        return $query->where('has_attachments', true);
    }

    public function scopeInThread($query, string $threadId)
    {
        return $query->where('thread_id', $threadId);
    }

    public function scopeFromDate($query, $date)
    {
        return $query->where('sent_at', '>=', $date);
    }

    public function scopeToDate($query, $date)
    {
        return $query->where('sent_at', '<=', $date);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('subject', 'like', "%{$term}%")
                ->orWhere('body_text', 'like', "%{$term}%")
                ->orWhere('from_address', 'like', "%{$term}%")
                ->orWhere('from_name', 'like', "%{$term}%");
        });
    }

    public function scopeFromSender($query, string $email)
    {
        return $query->where('from_address', $email);
    }

    public function scopeToRecipient($query, string $email)
    {
        return $query->where(function ($q) use ($email) {
            $q->whereJsonContains('to_addresses', $email)
                ->orWhereJsonContains('cc_addresses', $email)
                ->orWhereJsonContains('bcc_addresses', $email);
        });
    }
}
