<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacSeederTest extends TestCase
{
    use RefreshDatabase;

    private RbacSeeder $rbacSeeder;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RbacSeeder $rbacSeeder */
        $rbacSeeder = $this->app->make(RbacSeeder::class);
        $this->rbacSeeder = $rbacSeeder;
    }

    public function test_rbac_seeder_populates_expected_roles_and_permissions(): void
    {
        $this->rbacSeeder->run();

        $guard = (string) config('auth.defaults.guard', 'web');

        $expectedPermissions = collect(RbacSeeder::PERMISSION_SETS)
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            [],
            array_values(
                array_diff($expectedPermissions, Permission::query()->pluck('name')->all())
            ),
            'All expected permissions should exist'
        );

        $expectedRoles = array_keys(RbacSeeder::ROLE_MATRIX);

        foreach ($expectedRoles as $roleName) {
            $role = Role::findByName($roleName, $guard);

            $this->assertNotNull($role);
            $this->assertSame($guard, $role->guard_name);
        }

        $superAdmin = Role::findByName('super_admin', $guard);

        $this->assertNotNull($superAdmin);
        $this->assertSame($expectedPermissions, $superAdmin->permissions->pluck('name')->sort()->values()->all());

        $expected = collect(RbacSeeder::ROLE_MATRIX)
            ->except('super_admin')
            ->map(fn (array $permissionNames): array => collect($permissionNames)->sort()->values()->all());

        $actual = collect(RbacSeeder::ROLE_MATRIX)
            ->except('super_admin')
            ->map(function (array $permissionNames, string $roleName) use ($guard): array {
                $role = Role::findByName($roleName, $guard);

                return $role->permissions->pluck('name')->sort()->values()->all();
            });

        $this->assertSame($expected->toArray(), $actual->toArray());
    }
}
