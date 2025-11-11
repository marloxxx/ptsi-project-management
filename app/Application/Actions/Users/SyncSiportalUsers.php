<?php

declare(strict_types=1);

namespace App\Application\Actions\Users;

use App\Domain\Services\ExternalUserSyncServiceInterface;

class SyncSiportalUsers
{
    public function __construct(
        private ExternalUserSyncServiceInterface $externalUserSyncService
    ) {}

    /**
     * Execute the SI Portal sync for users and their organizational context.
     *
     * @return array{
     *     units: array{synced:int, created:int, updated:int, skipped:int},
     *     users: array{synced:int, created:int, updated:int, skipped:int, failed:int},
     *     errors: array<int, array{message:string, context:array<string, mixed>}>
     * }
     */
    public function execute(): array
    {
        return $this->externalUserSyncService->sync();
    }
}
