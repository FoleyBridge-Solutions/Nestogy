<?php

namespace App\Domains\Email\Traits;

use Illuminate\Support\Str;

trait HasEmailHelpers
{
    public function getThreadMessages(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->thread_id) {
            return collect([$this]);
        }

        return static::where('thread_id', $this->thread_id)
            ->orderBy('sent_at')
            ->get();
    }

    public function generatePreview(int $length = 200): string
    {
        $text = $this->body_text ?: strip_tags($this->body_html);

        return Str::limit($text, $length);
    }

    public function getAllRecipients(): array
    {
        $recipients = [];

        if ($this->to_addresses) {
            $recipients = array_merge($recipients, $this->to_addresses);
        }

        if ($this->cc_addresses) {
            $recipients = array_merge($recipients, $this->cc_addresses);
        }

        if ($this->bcc_addresses) {
            $recipients = array_merge($recipients, $this->bcc_addresses);
        }

        return array_unique($recipients);
    }

    public function isFromClient(): bool
    {
        return \App\Models\Client::where('email', $this->from_address)
            ->orWhereJsonContains('contact_emails', $this->from_address)
            ->exists();
    }

    public function getClientFromSender(): ?\App\Models\Client
    {
        return \App\Models\Client::where('email', $this->from_address)
            ->orWhereJsonContains('contact_emails', $this->from_address)
            ->first();
    }
}
