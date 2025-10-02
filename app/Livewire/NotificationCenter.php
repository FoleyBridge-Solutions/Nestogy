<?php

namespace App\Livewire;

use App\Models\InAppNotification;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Traits\HasFluxToasts;

class NotificationCenter extends Component
{
    use HasFluxToasts;
    public $notifications = [];

    public $unreadCount = 0;

    public $showDropdown = false;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $this->notifications = InAppNotification::query()
            ->forUser(auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->unreadCount = InAppNotification::query()
            ->forUser(auth()->id())
            ->unread()
            ->count();
    }

    public function markAsRead($notificationId)
    {
        $notification = InAppNotification::find($notificationId);
        
        if ($notification && $notification->user_id === auth()->id()) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        InAppNotification::query()
            ->forUser(auth()->id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->loadNotifications();
    }

    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    #[On('notification-created')]
    public function handleNewNotification()
    {
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notification-center');
    }
}
