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
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role>
     */
    public function all(): Collection
    {
        return $this->model->with('permissions')->get();
    }

    /**
     * Find Role by ID or name.
     */
    public function find(int|string $id): ?Role
    {
        $query = $this->model->newQuery()->with('permissions');

        if (is_numeric($id)) {
            return $query->find((int) $id);
        }

        return $query->where('name', (string) $id)->first();
    }

    /**
     * Create new Role.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        /** @var Role $role */
        $role = $this->model->create($data);

        return $role;
    }

    /**
     * Update Role.
     *
     * @param  array<string, mixed>  $data
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
