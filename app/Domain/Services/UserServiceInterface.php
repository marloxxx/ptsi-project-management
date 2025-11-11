<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User;

    /**
     * Create new user.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $roles
     */
    public function create(array $data, ?array $roles = null): User;

    /**
     * Update existing user.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $roles
     */
    public function update(int $id, array $data, ?array $roles = null): bool;

    /**
     * Delete user.
     */
    public function delete(int $id): bool;

    /**
     * Restore soft deleted user.
     */
    public function restore(int $id): bool;

    /**
     * Permanently delete user.
     */
    public function forceDelete(int $id): bool;

    /**
     * Assign roles to user.
     *
     * @param  array<int, string>  $roles
     */
    public function assignRoles(int $userId, array $roles): bool;
}
