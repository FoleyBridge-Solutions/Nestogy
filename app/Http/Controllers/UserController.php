<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Company;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
            $query->whereHas('userSetting', function ($q) use ($request) {
                $q->where('role', $request->get('role'));
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
     * Show the form for creating a new user
     */
    public function create()
    {
        $this->authorize('create', User::class);
        
        return view('users.create');
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        try {
            $userData = $this->userService->createUser($request->validated());
            
            Log::info('User created', [
                'user_id' => $userData['user_id'],
                'created_by' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user' => $userData
                ], 201);
            }

            return redirect()
                ->route('users.show', $userData['user_id'])
                ->with('success', "User <strong>{$userData['name']}</strong> created successfully");

        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'created_by' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user'
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
            }
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
                'stats' => $stats
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
        
        return view('users.edit', compact('user'));
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'user' => $updatedUser
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "User <strong>{$updatedUser->name}</strong> updated successfully");

        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user'
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
        if ($user->id === Auth::id() && !Hash::check($request->get('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        try {
            $this->userService->updateUserPassword($user, $request->get('password'));
            
            Log::info('User password updated', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password updated successfully'
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'Password updated successfully');

        } catch (\Exception $e) {
            Log::error('User password update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id()
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User role updated successfully'
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'User role updated successfully');

        } catch (\Exception $e) {
            Log::error('User role update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id()
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
                'ip' => $request->ip()
            ]);

            $statusText = $newStatus ? 'Active' : 'Inactive';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "User status updated to {$statusText}"
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "User status updated to {$statusText}");

        } catch (\Exception $e) {
            Log::error('User status update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id()
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User archived successfully'
                ]);
            }

            return redirect()
                ->route('users.index')
                ->with('success', "User <strong>{$user->name}</strong> archived successfully");

        } catch (\Exception $e) {
            Log::error('User archive failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'archived_by' => Auth::id()
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User restored successfully'
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "User <strong>{$user->name}</strong> restored successfully");

        } catch (\Exception $e) {
            Log::error('User restore failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'restored_by' => Auth::id()
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User permanently deleted'
                ]);
            }

            return redirect()
                ->route('users.index')
                ->with('success', "User <strong>{$userName}</strong> permanently deleted");

        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'deleted_by' => Auth::id()
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
        $user->load(['company']);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return view('users.profile', compact('user'));
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
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'user' => $updatedUser
                ]);
            }

            return redirect()
                ->route('users.profile')
                ->with('success', 'Profile updated successfully');

        } catch (\Exception $e) {
            Log::error('User profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
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
            'notifications_email' => 'boolean',
            'notifications_browser' => 'boolean',
            'theme' => 'nullable|in:light,dark,auto',
        ]);

        try {
            $this->userService->updateUserSettings($user, $request->all());
            
            Log::info('User settings updated', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);
            }

            return redirect()
                ->route('users.profile')
                ->with('success', 'Settings updated successfully');

        } catch (\Exception $e) {
            Log::error('User settings update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update settings');
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

        $filename = 'users-' . now()->format('Y-m-d') . '.csv';
        
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
                'Created Date'
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
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        Log::info('Users exported to CSV', [
            'count' => $users->count(),
            'exported_by' => Auth::id()
        ]);

        return response()->stream($callback, 200, $headers);
    }
}