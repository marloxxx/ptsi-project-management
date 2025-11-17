<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        Filament::setCurrentPanel('admin');
        $this->seed(RbacSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        // Clear permission cache to ensure fresh permissions
        $user->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user);

        return $user;
    }

    public function test_staff_cannot_access_role_creation(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('filament.admin.resources.roles.create'))
            ->assertForbidden();
    }

    public function test_admin_can_view_role_listing(): void
    {
        $this->actingAsRole('admin');

        $this->get(route('filament.admin.resources.roles.index'))
            ->assertOk()
            ->assertSee('Roles');
    }

    public function test_admin_can_create_role_via_filament_form(): void
    {
        $this->actingAsRole('admin');

        $permission = Permission::first();

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'custom_role',
                'guard_name' => 'web',
                'permissions' => $permission ? [$permission->name] : [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'custom_role',
            'guard_name' => 'web',
        ]);
    }

    public function test_admin_can_update_role(): void
    {
        $this->actingAsRole('admin');

        $role = Role::create([
            'name' => 'test_role',
            'guard_name' => 'web',
        ]);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm([
                'name' => 'updated_role',
                'guard_name' => 'web',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $role->refresh();

        $this->assertSame('updated_role', $role->name);
    }

    public function test_admin_can_delete_role(): void
    {
        $this->actingAsRole('admin');

        $role = Role::create([
            'name' => 'test_role',
            'guard_name' => 'web',
        ]);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->callAction('delete')
            ->assertNotified();

        $this->assertDatabaseMissing('roles', [
            'id' => $role->getKey(),
        ]);
    }
}
