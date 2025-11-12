<?php

declare(strict_types=1);

namespace App\Domain\Services;

/**
 * Provide read-only access to the canonical permission catalog used across
 * Spatie Permission seeding and Filament authorisation layers.
 */
interface PermissionCatalogServiceInterface
{
    /**
     * Get grouped permissions keyed by domain resource (e.g. users, roles).
     *
     * @return array<string, array<int, string>>
     */
    public function grouped(): array;

    /**
     * Flatten permission list for quick lookups.
     *
     * @return array<int, string>
     */
    public function all(): array;

    /**
     * Provide options array suitable for Filament CheckboxList grouping.
     *
     * @return array<string, string>
     */
    public function groupedOptions(): array;
}
