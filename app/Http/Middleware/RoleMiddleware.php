<?php

namespace App\Http\Middleware;

use App\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Handles role-based access control with hierarchical permissions.
 * Supports role hierarchy: Admin (3) > Tech (2) > Accountant (1)
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = $user->getRole();
        $requiredRole = $this->parseRole($role);

        // Check if user has sufficient role level
        if ($userRole < $requiredRole) {
            abort(403, 'Insufficient permissions. Required role: '.$this->getRoleLabel($requiredRole));
        }

        // Store current user role in request for easy access
        $request->attributes->set('user_role', $userRole);
        $request->attributes->set('user_role_label', $this->getRoleLabel($userRole));

        return $next($request);
    }

    /**
     * Parse role parameter to role level.
     */
    protected function parseRole(string $role): int
    {
        $roleMap = [
            'accountant' => UserSetting::ROLE_ACCOUNTANT,
            'tech' => UserSetting::ROLE_TECH,
            'technician' => UserSetting::ROLE_TECH,
            'admin' => UserSetting::ROLE_ADMIN,
            'administrator' => UserSetting::ROLE_ADMIN,
        ];

        // If it's a numeric role, validate it
        if (is_numeric($role)) {
            $numericRole = (int) $role;
            if (in_array($numericRole, [UserSetting::ROLE_ACCOUNTANT, UserSetting::ROLE_TECH, UserSetting::ROLE_ADMIN])) {
                return $numericRole;
            }
        }

        // If it's a string role, map it
        $lowerRole = strtolower($role);
        if (isset($roleMap[$lowerRole])) {
            return $roleMap[$lowerRole];
        }

        // Default to highest role requirement if invalid
        return UserSetting::ROLE_ADMIN;
    }

    /**
     * Get role label by role level.
     */
    protected function getRoleLabel(int $role): string
    {
        return UserSetting::ROLE_LABELS[$role] ?? 'Unknown';
    }
}
