<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

class NotificationCenter extends Component
{
    public $notifications;

    public $unreadCount = 0;

    public $showDropdown = false;
    
    public $isPushSubscribed = false;

    public function mount()
    {
        $this->loadNotifications();
        $this->checkPushSubscription();
    }

    public function loadNotifications()
    {
        $this->notifications = auth()->user()
            ->notifications()
            ->take(10)
            ->get();

        $this->unreadCount = auth()->user()
            ->unreadNotifications()
            ->count();
    }
    
    public function checkPushSubscription()
    {
        $user = auth()->user();
        
        if ($user && $user instanceof \App\Domains\Core\Models\User) {
            $this->isPushSubscribed = $user->pushSubscriptions()->exists();
        }
    }

    public function markAsRead($notificationId)
    {
        $notification = auth()->user()
            ->notifications()
            ->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;
    }
    
    public function subscribeToPush($subscriptionData)
    {
        try {
            $user = auth()->user();
            
            if (!$user || !($user instanceof \App\Domains\Core\Models\User)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Push notifications not available for your account type'
                ]);
                return;
            }

            $user->updatePushSubscription(
                $subscriptionData['endpoint'],
                $subscriptionData['keys']['p256dh'],
                $subscriptionData['keys']['auth'],
                $subscriptionData['contentEncoding'] ?? 'aesgcm'
            );

            $this->isPushSubscribed = true;
            $this->checkPushSubscription();

            Log::info('Push notification subscription created', [
                'user_id' => $user->id,
                'endpoint' => $subscriptionData['endpoint']
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Push notifications enabled!'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create push subscription', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to enable push notifications'
            ]);
        }
    }

    public function unsubscribeFromPush($endpoint)
    {
        try {
            $user = auth()->user();
            
            if (!$user || !($user instanceof \App\Domains\Core\Models\User)) {
                return;
            }

            $user->deletePushSubscription($endpoint);
            $this->isPushSubscribed = false;
            $this->checkPushSubscription();

            Log::info('Push notification subscription deleted', [
                'user_id' => $user->id,
                'endpoint' => $endpoint
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Push notifications disabled'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete push subscription', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.notifications.notification-center');
    }
}
