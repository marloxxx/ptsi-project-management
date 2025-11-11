<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\UserServiceInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}

    /**
     * Get all users.
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User
    {
        return $this->repository->find($id);
    }

    /**
     * Create new user with roles.
     */
    public function create(array $data, ?array $roles = null): User
    {
        return DB::transaction(function () use ($data, $roles) {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            // Create user
            $user = $this->repository->create($data);

            // Assign roles if provided
            if (! empty($roles)) {
                $user->syncRoles($roles);
            }

            // Log activity
            activity()
                ->performedOn($user)
                ->event('created')
                ->log('User created');

            return $user->fresh('roles');
        });
    }

    /**
     * Update existing user.
     */
    public function update(int $id, array $data, ?array $roles = null): bool
    {
        return DB::transaction(function () use ($id, $data, $roles) {
            $user = $this->repository->find($id);

            if (! $user) {
                return false;
            }

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            // Update user
            $this->repository->update($id, $data);

            // Sync roles if provided
            if ($roles !== null) {
                $user->syncRoles($roles);
            }

            // Log activity
            activity()
                ->performedOn($user)
                ->event('updated')
                ->log('User updated');

            return true;
        });
    }

    /**
     * Delete user.
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->repository->find($id);

            if (! $user) {
                return false;
            }

            // Log activity before deletion
            activity()
                ->performedOn($user)
                ->event('deleted')
                ->log('User deleted');

            return $this->repository->delete($id);
        });
    }

    /**
     * Restore soft deleted user.
     */
    public function restore(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $restored = $this->repository->restore($id);

            if ($restored) {
                $user = $this->repository->find($id);

                if ($user) {
                    activity()
                        ->performedOn($user)
                        ->event('restored')
                        ->log('User restored');
                }
            }

            return $restored;
        });
    }

    /**
     * Permanently delete user.
     */
    public function forceDelete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->repository->findWithTrashed($id);

            $deleted = $this->repository->forceDelete($id);

            if ($deleted && $user) {
                activity()
                    ->performedOn($user)
                    ->event('force-deleted')
                    ->log('User permanently deleted');
            }

            return $deleted;
        });
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(int $userId, array $roles): bool
    {
        return DB::transaction(function () use ($userId, $roles) {
            $user = $this->repository->find($userId);

            if (! $user) {
                return false;
            }

            $user->syncRoles($roles);

            // Log activity
            activity()
                ->performedOn($user)
                ->event('updated')
                ->withProperties(['roles' => $roles])
                ->log('User roles updated');

            return true;
        });
    }
}
