<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ExternalAccessTokenRepositoryInterface;
use App\Models\ExternalAccessToken;

class ExternalAccessTokenRepository implements ExternalAccessTokenRepositoryInterface
{
    public function find(int $id): ?ExternalAccessToken
    {
        return ExternalAccessToken::find($id);
    }

    public function findActiveForProject(int $projectId): ?ExternalAccessToken
    {
        return ExternalAccessToken::where('project_id', $projectId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExternalAccessToken
    {
        return ExternalAccessToken::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExternalAccessToken $token, array $data): ExternalAccessToken
    {
        $token->update($data);

        return $token->fresh();
    }

    public function delete(ExternalAccessToken $token): bool
    {
        return (bool) $token->delete();
    }
}
