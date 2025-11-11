<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\ExternalAccessToken;

interface ExternalAccessTokenRepositoryInterface
{
    public function find(int $id): ?ExternalAccessToken;

    public function findActiveForProject(int $projectId): ?ExternalAccessToken;

    public function create(array $data): ExternalAccessToken;

    public function update(ExternalAccessToken $token, array $data): ExternalAccessToken;

    public function delete(ExternalAccessToken $token): bool;
}

