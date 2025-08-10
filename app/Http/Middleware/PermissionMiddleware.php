<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * PermissionMiddleware
 * 
 * Handles permission-based access control using the new permissions system.
 * Supports both specific permissions and role-based access.
 */
class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The required permission or role
     * @param  string|null  $guard  The guard to use for authentication
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $guard = null): Response
    {
        $guard = $guard ?: config('auth.defaults.guard');

        if (!Auth::guard($guard)->check()) {
            return $this->unauthorized($request, 'Authentication required');
        }

        $user = Auth::guard($guard)->user();

        // Check if user has the required permission
        if (!$this->hasPermission($user, $permission)) {
            return $this->forbidden($request, "Missing required permission: {$permission}");
        }

        // Store permission info in request for easy access
        $request->attributes->set('required_permission', $permission);
        $request->attributes->set('user_permissions', $user->getAllPermissions()->pluck('slug')->toArray());

        return $next($request);
    }

    /**
     * Check if user has the required permission.
     */
    private function hasPermission($user, string $permission): bool
    {
        // Handle multiple permissions (separated by |)
        if (str_contains($permission, '|')) {
            $permissions = explode('|', $permission);
            return $user->hasAnyPermission($permissions);
        }

        // Handle multiple permissions (all required, separated by &)
        if (str_contains($permission, '&')) {
            $permissions = explode('&', $permission);
            return $user->hasAllPermissions($permissions);
        }

        // Check for role-based access (backward compatibility)
        if (in_array($permission, ['admin', 'technician', 'accountant'])) {
            return $this->checkRoleAccess($user, $permission);
        }

        // Check specific permission
        return $user->hasPermission($permission);
    }

    /**
     * Check role-based access (backward compatibility).
     */
    private function checkRoleAccess($user, string $role): bool
    {
        switch ($role) {
            case 'admin':
                return $user->isAdmin();
            case 'technician':
                return $user->isTech() || $user->isAdmin();
            case 'accountant':
                return $user->isAccountant() || $user->isTech() || $user->isAdmin();
            default:
                return $user->hasRole($role);
        }
    }

    /**
     * Handle unauthorized access.
     */
    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'Unauthenticated'
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Handle forbidden access.
     */
    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'Insufficient permissions'
            ], 403);
        }

        abort(403, $message);
    }
}