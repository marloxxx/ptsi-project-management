<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\Services\PermissionCatalogService;
use Database\Seeders\RbacSeeder;
use Tests\TestCase;

class PermissionCatalogServiceTest extends TestCase
{
    public function test_grouped_permissions_follow_rbac_seeder(): void
    {
        $service = new PermissionCatalogService;

        $this->assertSame(RbacSeeder::PERMISSION_SETS, $service->grouped());

        $expectedFlat = collect(RbacSeeder::PERMISSION_SETS)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $this->assertSame($expectedFlat, $service->all());
    }

    public function test_grouped_options_return_flat_label_value_pairs(): void
    {
        $service = new PermissionCatalogService;

        $options = $service->groupedOptions();

        $expected = collect(RbacSeeder::PERMISSION_SETS)
            ->flatMap(
                fn (array $permissions, string $group): array => collect($permissions)
                    ->mapWithKeys(fn (string $permission): array => [$permission => sprintf('%s: %s', \Illuminate\Support\Str::headline($group), \Illuminate\Support\Str::headline($permission))])
                    ->toArray()
            )
            ->toArray();

        $this->assertSame($expected, $options);
    }
}
