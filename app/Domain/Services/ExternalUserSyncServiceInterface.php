<?php

declare(strict_types=1);

namespace App\Domain\Services;

/**
 * Contract for syncing users (and related organizational context) from external systems.
 */
interface ExternalUserSyncServiceInterface
{
    /**
     * Sync users and their organizational context from an upstream provider.
     *
     * @param  callable|null  $progressCallback  Optional callback invoked after each entity is processed.
     *                                           Signature: fn(string $entity, array $payload, string $status): void
     * @return array{
     *     units: array{synced:int, created:int, updated:int, skipped:int},
     *     users: array{synced:int, created:int, updated:int, skipped:int, failed:int},
     *     errors: array<int, array{message:string, context:array<string, mixed>}>
     * }
     */
    public function sync(?callable $progressCallback = null): array;
}
