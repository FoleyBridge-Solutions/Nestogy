<?php

namespace App\Livewire\Settings;

use App\Models\NotificationPreference;
use Livewire\Component;
use App\Traits\HasFluxToasts;

class NotificationPreferences extends Component
{
    use HasFluxToasts;
    public $preferences;

    public $ticket_created = true;

    public $ticket_assigned = true;

    public $ticket_status_changed = true;

    public $ticket_resolved = true;

    public $ticket_comment_added = true;

    public $slaBreachWarning = true;

    public $sla_breached = true;

    public $daily_digest = false;

    public $emailEnabled = true;

    public $in_app_enabled = true;

    public $digestTime = '08:00';

    public function mount()
    {
        $this->preferences = NotificationPreference::getOrCreateForUser(auth()->user());

        $this->ticket_created = $this->preferences->ticket_created;
        $this->ticket_assigned = $this->preferences->ticket_assigned;
        $this->ticket_status_changed = $this->preferences->ticket_status_changed;
        $this->ticket_resolved = $this->preferences->ticket_resolved;
        $this->ticket_comment_added = $this->preferences->ticket_comment_added;
        $this->slaBreachWarning = $this->preferences->sla_breach_warning;
        $this->sla_breached = $this->preferences->sla_breached;
        $this->daily_digest = $this->preferences->daily_digest;
        $this->emailEnabled = $this->preferences->email_enabled;
        $this->in_app_enabled = $this->preferences->in_app_enabled;
        $this->digestTime = $this->preferences->digest_time;
    }

    public function save()
    {
        $this->preferences->update([
            'ticket_created' => $this->ticket_created,
            'ticket_assigned' => $this->ticket_assigned,
            'ticket_status_changed' => $this->ticket_status_changed,
            'ticket_resolved' => $this->ticket_resolved,
            'ticket_comment_added' => $this->ticket_comment_added,
            'sla_breach_warning' => $this->slaBreachWarning,
            'sla_breached' => $this->sla_breached,
            'daily_digest' => $this->daily_digest,
            'email_enabled' => $this->emailEnabled,
            'in_app_enabled' => $this->in_app_enabled,
            'digest_time' => $this->digestTime,
        ]);

        $this->success('Notification preferences saved successfully');
    }

    public function render()
    {
        return view('livewire.settings.notification-preferences');
    }
}
