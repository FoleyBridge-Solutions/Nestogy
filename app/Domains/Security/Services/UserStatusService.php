<?php

namespace App\Domains\Security\Services;

use App\Domains\Product\Services\SubscriptionService;
use App\Models\User;

class UserStatusService
{
    protected $subscriptionService;

    public function __construct()
    {
        $this->subscriptionService = app(SubscriptionService::class);
    }

    public function deactivateUser(User $user): User
    {
        $user->update(['active' => false]);

        return $user;
    }

    public function activateUser(User $user): User
    {
        $user->update(['active' => true]);

        return $user;
    }

    public function archiveUser(User $user): bool
    {
        $result = $user->delete();

        if ($result && $this->subscriptionService) {
            $this->subscriptionService->handleUserDeleted($user);
        }

        return $result;
    }

    public function restoreUser(User $user): bool
    {
        $result = $user->restore();

        if ($result && $this->subscriptionService) {
            $this->subscriptionService->handleUserCreated($user);
        }

        return $result;
    }

    public function updateUserStatus(User $user, bool $status): User
    {
        $oldStatus = $user->status;
        $user->update(['status' => $status]);

        if ($oldStatus != $status && $this->subscriptionService) {
            if ($status) {
                $this->subscriptionService->handleUserCreated($user);
            } else {
                $this->subscriptionService->handleUserDeleted($user);
            }
        }

        return $user;
    }

    public function updateLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }
}
