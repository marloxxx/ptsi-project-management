<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private Role $model
    ) {}

    /**
     * Get all Role records.
     */
    public function all(): Collection
    {
        return $this->model->with('permissions')->get();
    }

    /**
     * Find Role by ID.
     */
    public function find(int $id): ?Role
    {
        return $this->model->with('permissions')->find($id);
    }

    /**
     * Create new Role.
     */
    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    /**
     * Update Role.
     */
    public function update(int $id, array $data): bool
    {
        $role = $this->model->find($id);

        if (! $role) {
            return false;
        }

        return $role->update($data);
    }

    /**
     * Delete Role.
     */
    public function delete(int $id): bool
    {
        $role = $this->model->find($id);

        if (! $role) {
            return false;
        }

        return $role->delete();
    }
}
