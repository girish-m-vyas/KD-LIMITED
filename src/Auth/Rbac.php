<?php
declare(strict_types=1);

namespace KD\Auth;

/**
 * Role-based access control for the admin panel. Least privilege: each role
 * gets only the permissions it needs. Anything not listed is denied.
 */
final class Rbac
{
    public const PERMISSIONS = [
        'dashboard.view'   => 'View the dashboard',
        'catalog.manage'   => 'Manage products and categories',
        'content.manage'   => 'Manage slides, home boxes and terms',
        'orders.manage'    => 'View and update orders',
        'payments.view'    => 'View payments',
        'customers.manage' => 'View and delete customers',
        'messages.view'    => 'Read contact messages',
        'users.manage'     => 'Manage admin users and roles',
        'audit.view'       => 'View the audit log',
    ];

    public const ROLES = [
        'super_admin' => [
            'label'       => 'Super admin',
            'permissions' => ['*'],
        ],
        'catalog_manager' => [
            'label'       => 'Catalogue manager',
            'permissions' => ['dashboard.view', 'catalog.manage', 'content.manage'],
        ],
        'order_manager' => [
            'label'       => 'Order manager',
            'permissions' => ['dashboard.view', 'orders.manage', 'payments.view', 'customers.manage', 'messages.view'],
        ],
    ];

    public static function can(string $role, string $permission): bool
    {
        if (!isset(self::ROLES[$role], self::PERMISSIONS[$permission])) {
            return false;
        }
        $granted = self::ROLES[$role]['permissions'];
        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    public static function isRole(string $role): bool
    {
        return isset(self::ROLES[$role]);
    }

    public static function label(string $role): string
    {
        return self::ROLES[$role]['label'] ?? $role;
    }

    /** @return array<string, string> role => label */
    public static function roleOptions(): array
    {
        return array_map(fn ($r) => $r['label'], self::ROLES);
    }
}
