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
     * Base permissions grouped by domain resource.
     *
     * @var array<string, array<int, string>>
     */
    public const PERMISSION_SETS = [
        'users' => [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.restore',
            'users.force-delete',
        ],
        'roles' => [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.restore',
            'roles.force-delete',
        ],
        'units' => [
            'units.view',
            'units.create',
            'units.update',
            'units.delete',
            'units.restore',
            'units.force-delete',
        ],
        'projects' => [
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.manage-members',
            'projects.manage-statuses',
            'projects.manage-external-access',
            'projects.pin',
        ],
        'epics' => [
            'epics.view',
            'epics.create',
            'epics.update',
            'epics.delete',
        ],
        'project-notes' => [
            'project-notes.view',
            'project-notes.create',
            'project-notes.update',
            'project-notes.delete',
        ],
        'tickets' => [
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.comment',
        ],
        'audit-logs' => [
            'audit-logs.view',
        ],
        'reports' => [
            'reports.view',
            'reports.export',
        ],
        'approvals' => [
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.export',
        ],
    ];

    /**
     * Role matrix specifying the permission subset each role should own.
     *
     * @var array<string, array<int, string>>
     */
    public const ROLE_MATRIX = [
        'super_admin' => [], // Filled dynamically with the complete permission list.
        'admin' => [
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
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.manage-members',
            'projects.manage-statuses',
            'projects.manage-external-access',
            'projects.pin',
            'epics.view',
            'epics.create',
            'epics.update',
            'epics.delete',
            'project-notes.view',
            'project-notes.create',
            'project-notes.update',
            'project-notes.delete',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.comment',
        ],
        'manager' => [
            'users.view',
            'users.update',
            'units.view',
            'units.update',
            'reports.view',
            'reports.export',
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'projects.view',
            'projects.update',
            'projects.manage-members',
            'projects.manage-statuses',
            'epics.view',
            'epics.create',
            'epics.update',
            'project-notes.view',
            'project-notes.create',
            'project-notes.update',
            'project-notes.delete',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.comment',
        ],
        'staff' => [
            'reports.view',
            'approvals.view',
            'projects.view',
            'epics.view',
            'project-notes.view',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.comment',
        ],
    ];

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

        $roleMatrix = $this->resolveRoleMatrix($permissions);

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
        $permissions = collect(self::PERMISSION_SETS)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        collect($permissions)->each(
            fn (string $name) => Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['name' => $name]
            )
        );

        return $permissions;
    }

    /**
     * Resolve the permission matrix for each role.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function permissionsForAdmin(): array
    {
        return self::ROLE_MATRIX['admin'];
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForManager(): array
    {
        return self::ROLE_MATRIX['manager'];
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForStaff(): array
    {
        return self::ROLE_MATRIX['staff'];
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, array<int, string>>
     */
    private function resolveRoleMatrix(array $permissions): array
    {
        return collect(self::ROLE_MATRIX)
            ->map(function (array $permissionSubset, string $role) use ($permissions): array {
                if ($role === 'super_admin') {
                    return $permissions;
                }

                return $permissionSubset;
            })
            ->all();
    }
}
