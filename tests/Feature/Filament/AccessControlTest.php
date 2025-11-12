<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private RbacSeeder $rbacSeeder;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        /** @var RbacSeeder $rbacSeeder */
        $rbacSeeder = $this->app->make(RbacSeeder::class);
        $this->rbacSeeder = $rbacSeeder;
        $this->rbacSeeder->run();

        Filament::setCurrentPanel('admin');
    }

    public function test_staff_role_cannot_view_user_resource(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff);

        $response = $this->get(route('filament.admin.resources.users.index'));

        $response->assertForbidden();
    }

    public function test_staff_role_cannot_access_user_create_page(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff);

        $response = $this->get(route('filament.admin.resources.users.create'));

        $response->assertForbidden();
    }

    public function test_admin_role_can_view_user_resource(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->get(route('filament.admin.resources.users.index'));

        $response->assertOk();
        $response->assertSee('Users');
    }

    public function test_admin_role_can_access_user_create_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->get(route('filament.admin.resources.users.create'));

        $response->assertOk();
        $response->assertSee('User');
    }

    public function test_manager_role_cannot_manage_roles_resource(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager);

        $response = $this->get(route('filament.admin.resources.roles.index'));

        $response->assertForbidden();
    }

    public function test_manager_role_cannot_access_role_create_page(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager);

        $response = $this->get(route('filament.admin.resources.roles.create'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_roles_resource(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        $response = $this->get(route('filament.admin.resources.roles.index'));

        $response->assertOk();
        $response->assertSee('Roles');
    }

    public function test_super_admin_can_access_role_create_page(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        $response = $this->get(route('filament.admin.resources.roles.create'));

        $response->assertOk();
        $response->assertSee('Role');
    }
}
