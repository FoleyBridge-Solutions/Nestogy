<?php

namespace Twetech\Nestogy\Auth;

class Permissions {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if user has permission(s)
     * Supports checking multiple permissions with AND/OR logic
     */
    public function can(int $userId, $permissions, string $logic = 'AND'): bool {
        // Handle single permission check
        if (is_string($permissions)) {
            return $this->hasPermission($userId, $permissions);
        }
        
        // Handle multiple permission checks
        if (!is_array($permissions)) {
            throw new \InvalidArgumentException('Permissions must be string or array');
        }
        
        $results = array_map(
            fn($permission) => $this->hasPermission($userId, $permission),
            $permissions
        );
        
        return $logic === 'AND' ? !in_array(false, $results, true) : in_array(true, $results, true);
    }
    
    /**
     * Check single permission with support for wildcards and inheritance
     */
    protected function hasPermission(int $userId, string $permission): bool {
        $userPermissions = $this->getUserPermissions($userId);
        
        // Direct permission check
        if (isset($userPermissions[$permission])) {
            return true;
        }
        
        // Wildcard check (e.g., "clients.*" matches "clients.view")
        $parts = explode('.', $permission);
        while (array_pop($parts) !== null) {
            $wildcardPermission = implode('.', $parts) . '.*';
            if (isset($userPermissions[$wildcardPermission])) {
                return true;
            }
        }
        
        // Super admin check
        return isset($userPermissions['*']);
    }
    
    /**
     * Get all permissions for a user
     */
    protected function getUserPermissions(int $userId): array {
        $stmt = $this->pdo->prepare("
            WITH RECURSIVE role_hierarchy AS (
                -- Base case: direct roles
                SELECT r.role_id, r.parent_role_id, r.role_name
                FROM roles r
                JOIN user_roles ur ON ur.role_id = r.role_id
                WHERE ur.user_id = :user_id
                
                UNION ALL
                
                -- Recursive case: parent roles
                SELECT r.role_id, r.parent_role_id, r.role_name
                FROM roles r
                JOIN role_hierarchy rh ON r.role_id = rh.parent_role_id
            )
            SELECT DISTINCT p.permission_name
            FROM role_hierarchy rh
            JOIN role_permissions rp ON rp.role_id = rh.role_id
            JOIN permissions p ON p.permission_id = rp.permission_id
            WHERE rp.tenant_id = (
                SELECT user_tenant_id FROM users WHERE user_id = :user_id
            )
        ");
        
        $stmt->execute(['user_id' => $userId]);
        
        $permissions = [];
        while ($row = $stmt->fetch()) {
            $permissions[$row['permission_name']] = true;
        }
        
        return $permissions;
    }
    
    /**
     * Bulk check permissions for multiple users
     */
    public function bulkCan(array $userIds, string $permission): array {
        $results = [];
        foreach ($userIds as $userId) {
            $results[$userId] = $this->can($userId, $permission);
        }
        return $results;
    }
    
    /**
     * Get all permissions for a specific resource
     */
    public function getResourcePermissions(int $userId, string $resource): array {
        $permissions = $this->getUserPermissions($userId);
        
        return array_filter(
            array_keys($permissions),
            fn($permission) => strpos($permission, $resource . '.') === 0
        );
    }
    
    public function getAllRoles(): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, COUNT(DISTINCT ur.user_id) as user_count
            FROM roles r
            LEFT JOIN user_roles ur ON ur.role_id = r.role_id
            WHERE r.tenant_id = :tenant_id
            GROUP BY r.role_id
        ");
        
        $stmt->execute(['tenant_id' => $this->getCurrentTenantId()]);
        return $stmt->fetchAll();
    }
    
    public function getRolePermissions(int $roleId): array {
        $stmt = $this->pdo->prepare("
            SELECT p.*
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.permission_id
            WHERE rp.role_id = :role_id
            AND rp.tenant_id = :tenant_id
        ");
        
        $stmt->execute([
            'role_id' => $roleId,
            'tenant_id' => $this->getCurrentTenantId()
        ]);
        return $stmt->fetchAll();
    }
    
    public function createRole(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO roles (tenant_id, role_name, role_description, parent_role_id)
            VALUES (:tenant_id, :role_name, :role_description, :parent_role_id)
        ");
        
        $stmt->execute([
            'tenant_id' => $this->getCurrentTenantId(),
            'role_name' => $data['role_name'],
            'role_description' => $data['role_description'],
            'parent_role_id' => $data['parent_role_id'] ?: null
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    protected function getCurrentTenantId(): int {
        // Implement based on your tenant management system
        return $_SESSION['tenant_id'] ?? 1;
    }
} 