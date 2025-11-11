<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all User records.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Find User by ID.
     */
    public function find(int $id): ?User;

    /**
     * Find User by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find User by national identifier (NIK).
     */
    public function findByNik(string $nik): ?User;

    /**
     * Find User including soft deleted records.
     */
    public function findWithTrashed(int $id): ?User;

    /**
     * Create new User.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Update User.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete User.
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
}
