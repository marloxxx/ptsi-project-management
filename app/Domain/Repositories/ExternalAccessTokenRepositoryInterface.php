<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\ExternalAccessToken;

interface ExternalAccessTokenRepositoryInterface
{
    public function find(int $id): ?ExternalAccessToken;

    public function findActiveForProject(int $projectId): ?ExternalAccessToken;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExternalAccessToken;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExternalAccessToken $token, array $data): ExternalAccessToken;

    public function delete(ExternalAccessToken $token): bool;
}
