<?php

namespace App\Domains\Security\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function updateUserPassword(User $user, string $password): User
    {
        $user->update(['password' => Hash::make($password)]);

        return $user;
    }

    public function updateUserRole(User $user, $role): User
    {
        $this->roleService->assignRole($user, $role, $user->company_id);

        return $user->fresh();
    }

    public function updateUserProfile(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            $profileData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], function ($value) {
                return $value !== null;
            });

            if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                if ($user->avatar) {
                    Storage::disk(config('filesystems.default'))->delete('users/'.$user->avatar);
                }

                $avatarPath = $data['avatar']->store('users', config('filesystems.default'));
                $profileData['avatar'] = basename($avatarPath);
            }

            $user->update($profileData);

            DB::commit();

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateUserSettings(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            $userSettings = array_filter([
                'timezone' => $data['timezone'] ?? null,
                'date_format' => $data['date_format'] ?? null,
                'time_format' => $data['time_format'] ?? null,
            ], function ($value) {
                return $value !== null;
            });

            if (! empty($userSettings)) {
                $user->update($userSettings);
            }

            if ($user->userSetting) {
                $settingsData = array_filter([
                    'force_mfa' => isset($data['force_mfa']) ? (bool) $data['force_mfa'] : null,
                    'records_per_page' => isset($data['records_per_page']) ? (int) $data['records_per_page'] : null,
                    'dashboard_financial_enable' => isset($data['dashboard_financial_enable']) ? (bool) $data['dashboard_financial_enable'] : null,
                    'dashboard_technical_enable' => isset($data['dashboard_technical_enable']) ? (bool) $data['dashboard_technical_enable'] : null,
                    'theme' => isset($data['theme']) ? $data['theme'] : null,
                ], function ($value) {
                    return $value !== null;
                });

                if (! empty($settingsData)) {
                    $user->userSetting->update($settingsData);
                }
            }

            DB::commit();

            return $user->fresh(['userSetting']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
