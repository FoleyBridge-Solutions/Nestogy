<?php

namespace App\Domains\Security\Controllers;

use App\Domains\Product\Services\SubscriptionService;
use App\Domains\Security\Services\RoleService;
use App\Domains\Security\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $userService;

    protected $roleService;

    protected $subscriptionService;

    public function __construct(UserService $userService, RoleService $roleService, SubscriptionService $subscriptionService)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = User::with(['company', 'userSetting'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at');

        // Apply filters
        if ($request->filled('role')) {
            // Filter by Bouncer role name instead of legacy integer role
            $roleName = $request->get('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('users.index', compact('users'));
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $user = Auth::user();
        $query = User::query();

        // Company filtering for non-super-admins
        if (! $user->canAccessCrossTenant()) {
            $query->where('company_id', $user->company_id);
        }

        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->whereNull('archived_at');
            } elseif ($status === 'archived') {
                $query->whereNotNull('archived_at');
            }
        }

        $users = $query->with('company')->orderBy('name')->get();

        // Generate CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, ['Name', 'Email', 'Company', 'Role', 'Status', 'Created At']);

            // CSV data
            foreach ($users as $user) {
                $role = $this->roleService->getUserRole($user);
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->company->name ?? 'N/A',
                    $role['display_name'] ?? 'User',
                    $user->archived_at ? 'Archived' : 'Active',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $this->authorize('create', User::class);

        $user = Auth::user();

        // Super-admins can add users to any company
        if ($user->canAccessCrossTenant()) {
            $companies = Company::where('is_active', true)->orderBy('name')->get();
        } else {
            // Regular admins can only add users to their own company
            $companies = collect([$user->company]);
        }

        // Get available roles based on current user's permissions
        // Get all Bouncer roles and filter based on hierarchy
        $availableRoles = $this->getAvailableRolesForUser($user);

        return view('users.create', compact('companies', 'availableRoles'));
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        try {
            // Check subscription limits before creating user
            $company = Auth::user()->company;

            // If a different company is specified (for super-admins), use that
            if ($request->has('company_id') && Auth::user()->canAccessCrossTenant()) {
                $company = Company::findOrFail($request->company_id);
            }

            // Enforce user limits (will throw exception if limit reached)
            $this->subscriptionService->enforceUserLimits($company);

            $userData = $this->userService->createUser($request->validated());

            Log::info('User created', [
                'user_id' => $userData['user_id'],
                'created_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user' => $userData,
                ], 201);
            }

            return redirect()
                ->route('users.show', $userData['user_id'])
                ->with('success', "User <strong>{$userData['name']}</strong> created successfully");

        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create user');
        }
    }

    /**
     * Display the specified user
     */
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $user->load([
            'company',
            'userSetting',
            'createdTickets' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'assignedTickets' => function ($query) {
                $query->whereIn('status', ['Open', 'In Progress', 'Waiting'])
                    ->orderBy('created_at', 'desc');
            },
        ]);

        // Get user statistics
        $stats = [
            'total_tickets_created' => $user->createdTickets()->count(),
            'total_tickets_assigned' => $user->assignedTickets()->count(),
            'open_tickets_assigned' => $user->assignedTickets()
                ->whereIn('status', ['Open', 'In Progress', 'Waiting'])->count(),
            'last_login' => $user->last_login_at,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'user' => $user,
                'stats' => $stats,
            ]);
        }

        return view('users.show', compact('user', 'stats'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);

        // Get available roles based on current user's permissions
        $currentUser = Auth::user();
        $availableRoles = $this->getAvailableRolesForUser($currentUser);

        return view('users.edit', compact('user', 'availableRoles'));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'user' => $updatedUser,
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "User <strong>{$updatedUser->name}</strong> updated successfully");

        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update user');
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $request->validate([
            'current_password' => $user->id === Auth::id() ? 'required|string' : 'nullable',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // If updating own password, verify current password
        if ($user->id === Auth::id() && ! Hash::check($request->get('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        try {
            $this->userService->updateUserPassword($user, $request->get('password'));

            Log::info('User password updated', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password updated successfully',
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'Password updated successfully');

        } catch (\Exception $e) {
            Log::error('User password update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update password');
        }
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, User $user)
    {
        $this->authorize('updateRole', $user);

        $request->validate([
            'role' => 'required|in:1,2,3,4', // 1=User, 2=Tech, 3=Admin, 4=Super Admin
        ]);

        try {
            $oldRole = $user->userSetting->role ?? 1;
            $newRole = $request->get('role');

            $this->userService->updateUserRole($user, $newRole);

            Log::info('User role updated', [
                'user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'updated_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User role updated successfully',
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'User role updated successfully');

        } catch (\Exception $e) {
            Log::error('User role update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update user role');
        }
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $request->validate([
            'status' => 'required|in:0,1', // 0=Inactive, 1=Active
        ]);

        try {
            $oldStatus = $user->status;
            $newStatus = $request->get('status');

            $this->userService->updateUserStatus($user, $newStatus);

            Log::info('User status updated', [
                'user_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            $statusText = $newStatus ? 'Active' : 'Inactive';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "User status updated to {$statusText}",
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "User status updated to {$statusText}");

        } catch (\Exception $e) {
            Log::error('User status update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update user status');
        }
    }

    /**
     * Archive the specified user
     */
    public function archive(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        // Prevent archiving self
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot archive yourself');
        }

        try {
            $this->userService->archiveUser($user);

            Log::info('User archived', [
                'user_id' => $user->id,
                'archived_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User archived successfully',
                ]);
            }

            return redirect()
                ->route('users.index')
                ->with('success', "User <strong>{$user->name}</strong> archived successfully");

        } catch (\Exception $e) {
            Log::error('User archive failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'archived_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to archive user');
        }
    }

    /**
     * Restore archived user
     */
    public function restore(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('restore', $user);

        try {
            $this->userService->restoreUser($user);

            Log::info('User restored', [
                'user_id' => $user->id,
                'restored_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User restored successfully',
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "User <strong>{$user->name}</strong> restored successfully");

        } catch (\Exception $e) {
            Log::error('User restore failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'restored_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to restore user');
        }
    }

    /**
     * Permanently delete the specified user
     */
    public function destroy(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user);

        // Prevent deleting self
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete yourself');
        }

        try {
            $userName = $user->name;
            $this->userService->deleteUser($user);

            Log::warning('User permanently deleted', [
                'user_id' => $user->id,
                'user_name' => $userName,
                'deleted_by' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User permanently deleted',
                ]);
            }

            return redirect()
                ->route('users.index')
                ->with('success', "User <strong>{$userName}</strong> permanently deleted");

        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'deleted_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to delete user');
        }
    }

    /**
     * Show user profile
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        $user->load(['company', 'userSetting']);

        // Ensure user has settings
        if (! $user->userSetting) {
            $user->userSetting = UserSetting::createDefaultForUser($user->id, UserSetting::ROLE_ACCOUNTANT, $user->company_id);
        }

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        // Navigation context for sidebar
        $activeDomain = 'settings';
        $activeItem = 'profile';

        return view('users.profile', compact('user', 'activeDomain', 'activeItem'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $updatedUser = $this->userService->updateUserProfile($user, $request->all());

            Log::info('User profile updated', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'user' => $updatedUser,
                ]);
            }

            return redirect()
                ->route('users.profile')
                ->with('success', 'Profile updated successfully');

        } catch (\Exception $e) {
            Log::error('User profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update profile');
        }
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:20',
            'records_per_page' => 'nullable|integer|min:10|max:100',
            'dashboard_financial_enable' => 'nullable|boolean',
            'dashboard_technical_enable' => 'nullable|boolean',
            'notifications_email' => 'nullable|boolean',
            'notifications_browser' => 'nullable|boolean',
            'theme' => 'nullable|in:light,dark,auto',
        ]);

        try {
            // Ensure user has settings
            if (! $user->userSetting) {
                $user->userSetting = UserSetting::createDefaultForUser($user->id, UserSetting::ROLE_ACCOUNTANT, $user->company_id);
            }

            // Update UserSetting fields
            if ($request->has('records_per_page')) {
                $user->userSetting->update(['records_per_page' => $request->get('records_per_page')]);
            }

            if ($request->has('dashboard_financial_enable')) {
                $user->userSetting->update(['dashboard_financial_enable' => $request->boolean('dashboard_financial_enable')]);
            }

            if ($request->has('dashboard_technical_enable')) {
                $user->userSetting->update(['dashboard_technical_enable' => $request->boolean('dashboard_technical_enable')]);
            }

            if ($request->has('theme')) {
                $user->userSetting->update(['theme' => $request->get('theme')]);
            }

            // Update other user preferences through the service
            $this->userService->updateUserSettings($user, $request->all());

            Log::info('User settings updated', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully',
                ]);
            }

            return redirect()
                ->route('users.profile')
                ->with('success', 'Settings updated successfully');

        } catch (\Exception $e) {
            Log::error('User settings update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update settings');
        }
    }

    /**
     * Update own password from profile
     */
    public function updateOwnPassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
            'force_mfa' => 'nullable|boolean',
        ]);

        try {
            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Update MFA setting if provided
            if ($request->has('force_mfa')) {
                // Ensure user has settings
                if (! $user->userSetting) {
                    $user->userSetting = UserSetting::createDefaultForUser($user->id, UserSetting::ROLE_ACCOUNTANT, $user->company_id);
                }

                $user->userSetting->update(['force_mfa' => $request->boolean('force_mfa')]);
            }

            Log::info('User password updated', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Security settings updated successfully',
                ]);
            }

            return redirect()
                ->route('users.profile')
                ->with('success', 'Security settings updated successfully');

        } catch (\Exception $e) {
            Log::error('User password update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update security settings');
        }
    }

    /**
     * Get active technicians for dropdowns
     */
    public function getActiveTechnicians(Request $request)
    {
        $user = Auth::user();

        $technicians = User::with('userSetting')
            ->where('company_id', $user->company_id)
            ->where('status', 1)
            ->whereNull('archived_at')
            ->whereHas('userSetting', function ($query) {
                $query->where('role', '>', 1); // Tech role and above
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($technicians);
    }

    /**
     * Get user activity log
     */
    public function getActivityLog(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $logs = DB::table('logs')
            ->where('log_user_id', $user->id)
            ->orderBy('log_created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    /**
     * Export users to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();

        $users = User::with(['userSetting'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $filename = 'users-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Name',
                'Email',
                'Phone',
                'Role',
                'Status',
                'Last Login',
                'Created Date',
            ]);

            // CSV data
            foreach ($users as $user) {
                $roleNames = [1 => 'User', 2 => 'Tech', 3 => 'Admin', 4 => 'Super Admin'];
                $role = $roleNames[$user->userSetting->role ?? 1] ?? 'User';

                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->phone,
                    $role,
                    $user->status ? 'Active' : 'Inactive',
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        Log::info('Users exported to CSV', [
            'count' => $users->count(),
            'exported_by' => Auth::id(),
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'theme' => 'nullable|in:light,dark,system',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|timezone',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|in:12,24',
            'email_notifications' => 'nullable|boolean',
            'desktop_notifications' => 'nullable|boolean',
        ]);

        try {
            // Get or create user settings
            if (! $user->userSetting) {
                $user->userSetting = UserSetting::createDefaultForUser($user->id, UserSetting::ROLE_USER, $user->company_id);
            }

            // Update preferences
            $preferences = $user->userSetting->preferences ?? [];
            $preferences['theme'] = $request->input('theme', 'light');
            $preferences['language'] = $request->input('language', 'en');
            $preferences['timezone'] = $request->input('timezone', 'UTC');
            $preferences['date_format'] = $request->input('date_format', 'm/d/Y');
            $preferences['time_format'] = $request->input('time_format', '12');
            $preferences['email_notifications'] = $request->boolean('email_notifications');
            $preferences['desktop_notifications'] = $request->boolean('desktop_notifications');

            $user->userSetting->preferences = $preferences;
            $user->userSetting->save();

            Log::info('User preferences updated', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->route('users.profile')
                ->with('success', 'Preferences updated successfully');

        } catch (\Exception $e) {
            Log::error('User preferences update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update preferences');
        }
    }

    /**
     * Delete user account
     */
    public function destroyAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password
        if (! Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid password');
        }

        try {
            // Archive the user instead of hard delete
            $user->archived_at = now();
            $user->status = 0;
            $user->save();

            Log::info('User account deleted', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            Auth::logout();

            return redirect('/')->with('success', 'Your account has been deleted');

        } catch (\Exception $e) {
            Log::error('User account deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete account');
        }
    }

    /**
     * Get available roles based on user's permission level
     */
    private function getAvailableRolesForUser($user)
    {
        // Define role hierarchy
        $roleHierarchy = [
            'super-admin' => 4,
            'admin' => 3,
            'technician' => 2,
            'accountant' => 2,
            'sales-representative' => 2,
            'marketing-specialist' => 2,
            'user' => 1,
            'client-user' => 1,
        ];

        // Get all Bouncer roles
        $allRoles = \Silber\Bouncer\BouncerFacade::role()->get();

        // Determine the user's highest role level
        $userRoleLevel = 1;
        if ($user->isA('super-admin')) {
            $userRoleLevel = 4;
        } elseif ($user->isA('admin')) {
            $userRoleLevel = 3;
        } elseif ($user->isA('technician') || $user->isA('accountant') ||
                  $user->isA('sales-representative') || $user->isA('marketing-specialist')) {
            $userRoleLevel = 2;
        }

        // Filter roles and format for dropdown
        $availableRoles = [];
        foreach ($allRoles as $role) {
            $roleLevel = $roleHierarchy[$role->name] ?? 1;

            // Only include roles at or below user's level
            // Exclude super-admin unless user is super-admin
            if ($role->name === 'super-admin' && $userRoleLevel < 4) {
                continue;
            }

            if ($roleLevel <= $userRoleLevel) {
                // Use role ID as key and title/name as value
                $availableRoles[$role->id] = $role->title ?: ucwords(str_replace('-', ' ', $role->name));
            }
        }

        return $availableRoles;
    }
}
