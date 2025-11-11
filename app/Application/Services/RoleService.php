<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\RoleRepositoryInterface;
use App\Domain\Services\RoleServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService implements RoleServiceInterface
{
    public function __construct(
        protected RoleRepositoryInterface $repository
    ) {}

    /**
     * Get all roles.
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Find role by ID.
     */
    public function find(int $id): ?Role
    {
        return $this->repository->find($id);
    }

    /**
     * Create new role with permissions.
     */
    public function create(array $data, ?array $permissions = null): Role
    {
        return DB::transaction(function () use ($data, $permissions) {
            // Create role
            $role = $this->repository->create($data);

            // Sync permissions if provided
            if (! empty($permissions)) {
                $this->syncRolePermissions($role->id, $permissions);
            }

            $log = activity()
                ->performedOn($role)
                ->event('created');

            if (! empty($permissions)) {
                $log->withProperties(['permissions' => array_values($permissions)]);
            }

            $log->log('Role created');

            return $role->fresh('permissions');
        });
    }

    /**
     * Update existing role.
     */
    public function update(int $id, array $data, ?array $permissions = null): bool
    {
        return DB::transaction(function () use ($id, $data, $permissions) {
            $role = $this->repository->find($id);

            if (! $role) {
                return false;
            }

            // Update role
            $this->repository->update($id, $data);

            // Sync permissions if provided
            if ($permissions !== null) {
                $this->syncRolePermissions($id, $permissions);
            }

            $log = activity()
                ->performedOn($role)
                ->event('updated');

            if ($permissions !== null) {
                $log->withProperties(['permissions' => array_values($permissions)]);
            }

            $log->log('Role updated');

            return true;
        });
    }

    /**
     * Delete role.
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $role = $this->repository->find($id);

            if (! $role) {
                return false;
            }

            // Log activity before deletion
            activity()
                ->performedOn($role)
                ->event('deleted')
                ->log('Role deleted');

            return $this->repository->delete($id);
        });
    }

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(int $roleId, array $permissions): bool
    {
        return DB::transaction(function () use ($roleId, $permissions) {
            $role = $this->repository->find($roleId);

            if (! $role) {
                return false;
            }

            $this->syncRolePermissions($roleId, $permissions);

            $log = activity()
                ->performedOn($role)
                ->event('updated');

            if (! empty($permissions)) {
                $log->withProperties(['permissions' => array_values($permissions)]);
            }

            $log->log('Role permissions synced');

            return true;
        });
    }

    /**
     * Sync role permissions (internal helper).
     */
    private function syncRolePermissions(int $roleId, array $permissions): void
    {
        $role = $this->repository->find($roleId);

        if (! $role) {
            return;
        }

        $permissionModels = collect();

        foreach ($permissions as $permission) {
            $permissionModel = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $role->guard_name,
            ]);

            $permissionModels->push($permissionModel);
        }

        $role->syncPermissions($permissionModels);
    }
}
