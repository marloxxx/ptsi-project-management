<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed base roles and permissions for the application.
 */
class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = (string) config('auth.defaults.guard', 'web');

        /** @var PermissionRegistrar $registrar */
        $registrar = app()->make(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $permissions = $this->seedPermissions($guard);
        $registrar->forgetCachedPermissions();

        $roleMatrix = [
            'super_admin' => $permissions,
            'admin' => $this->permissionsForAdmin(),
            'manager' => $this->permissionsForManager(),
            'staff' => $this->permissionsForStaff(),
        ];

        collect($roleMatrix)->each(function (array $permissionNames, string $roleName) use ($guard): void {
            $role = Role::findOrCreate($roleName, $guard);

            $permissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->where('guard_name', $guard)
                ->get();

            $role->syncPermissions($permissions);
        });
    }

    /**
     * Seed the full permission list.
     *
     * @return array<int, string>
     */
    private function seedPermissions(string $guard): array
    {
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.restore',
            'users.force-delete',

            // Role management
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.restore',
            'roles.force-delete',

            // Unit management
            'units.view',
            'units.create',
            'units.update',
            'units.delete',
            'units.restore',
            'units.force-delete',

            // Activity logs
            'audit-logs.view',

            // Reports
            'reports.view',
            'reports.export',

            // Approval workflows
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.export',
        ];

        collect($permissions)->each(
            fn (string $name) => Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['name' => $name]
            )
        );

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForAdmin(): array
    {
        return [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.restore',
            'users.force-delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.restore',
            'roles.force-delete',
            'units.view',
            'units.create',
            'units.update',
            'units.delete',
            'units.restore',
            'units.force-delete',
            'audit-logs.view',
            'reports.view',
            'reports.export',
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.export',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForManager(): array
    {
        return [
            'users.view',
            'users.update',
            'units.view',
            'units.update',
            'reports.view',
            'reports.export',
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForStaff(): array
    {
        return [
            'reports.view',
            'approvals.view',
        ];
    }
}
