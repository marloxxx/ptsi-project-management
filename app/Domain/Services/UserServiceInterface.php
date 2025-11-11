<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    /**
     * Get all users.
     */
    public function all(): Collection;

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User;

    /**
     * Create new user.
     */
    public function create(array $data, ?array $roles = null): User;

    /**
     * Update existing user.
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
     */
    public function assignRoles(int $userId, array $roles): bool;
}
