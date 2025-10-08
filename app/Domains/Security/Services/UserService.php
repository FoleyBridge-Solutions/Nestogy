<?php

namespace App\Domains\Security\Services;

use App\Domains\Product\Services\SubscriptionService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $roleService;
    protected $subscriptionService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
        $this->subscriptionService = app(SubscriptionService::class);
    }

    public function createUser(array $data): User
    {
        DB::beginTransaction();

        try {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $data['active'] = $data['active'] ?? true;
            $data['email_verified_at'] = $data['email_verified_at'] ?? now();

            $user = User::create($data);

            $roleToAssign = null;

            if (! empty($data['role'])) {
                $roleToAssign = (int) $data['role'];
            } elseif (! empty($data['roles'])) {
                $roleToAssign = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
            }

            if ($roleToAssign !== null) {
                $this->roleService->assignRole($user, $roleToAssign, $user->company_id);
            }

            DB::commit();

            if ($this->subscriptionService) {
                $this->subscriptionService->handleUserCreated($user);
            }

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            $roleToAssign = null;

            if (isset($data['role'])) {
                $roleToAssign = (int) $data['role'];
            } elseif (isset($data['roles'])) {
                $roleToAssign = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
            }

            if ($roleToAssign !== null) {
                $this->roleService->assignRole($user, $roleToAssign, $user->company_id);
            }

            DB::commit();

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deleteUser(User $user): bool
    {
        if ($this->subscriptionService) {
            $this->subscriptionService->handleUserDeleted($user);
        }

        return $user->forceDelete();
    }
}
