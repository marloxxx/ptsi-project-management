<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private User $model
    ) {}

    /**
     * Get all User records.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return $this->model->with('roles', 'permissions', 'unit')->get();
    }

    /**
     * Find User by ID.
     */
    public function find(int $id): ?User
    {
        return $this->model->with('roles', 'permissions', 'unit')->find($id);
    }

    /**
     * Find User by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find User by NIK.
     */
    public function findByNik(string $nik): ?User
    {
        return $this->model->where('nik', $nik)->first();
    }

    /**
     * Find User including soft deleted records.
     */
    public function findWithTrashed(int $id): ?User
    {
        return $this->model->withTrashed()->with('roles', 'permissions', 'unit')->find($id);
    }

    /**
     * Create new User.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    /**
     * Update User.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $user = $this->model->find($id);

        if (! $user) {
            return false;
        }

        return $user->update($data);
    }

    /**
     * Delete User.
     */
    public function delete(int $id): bool
    {
        $user = $this->model->find($id);

        if (! $user) {
            return false;
        }

        return $user->delete();
    }

    /**
     * Restore soft deleted user.
     */
    public function restore(int $id): bool
    {
        $user = $this->model->onlyTrashed()->find($id);

        if (! $user) {
            return false;
        }

        return (bool) $user->restore();
    }

    /**
     * Permanently delete user.
     */
    public function forceDelete(int $id): bool
    {
        $user = $this->model->withTrashed()->find($id);

        if (! $user) {
            return false;
        }

        return (bool) $user->forceDelete();
    }
}
