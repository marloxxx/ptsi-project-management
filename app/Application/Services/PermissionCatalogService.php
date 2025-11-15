<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Services\PermissionCatalogServiceInterface;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Str;

class PermissionCatalogService implements PermissionCatalogServiceInterface
{
    public function grouped(): array
    {
        return RbacSeeder::PERMISSION_SETS;
    }

    public function all(): array
    {
        return collect($this->grouped())
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    public function groupedOptions(): array
    {
        return collect($this->grouped())
            ->flatMap(
                fn (array $permissions, string $group): array => collect($permissions)
                    ->mapWithKeys(function (string $permission) use ($group): array {
                        $label = Str::headline($permission);
                        $groupLabel = Str::headline($group);

                        return [$permission => "{$groupLabel}: {$label}"];
                    })
                    ->toArray()
            )
            ->toArray();
    }
}
