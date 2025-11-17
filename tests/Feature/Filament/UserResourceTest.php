<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

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

    public function test_staff_cannot_access_user_creation(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('filament.admin.resources.users.create'))
            ->assertForbidden();
    }

    public function test_admin_can_view_user_listing(): void
    {
        $this->actingAsRole('admin');

        $this->get(route('filament.admin.resources.users.index'))
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_admin_can_create_user_via_filament_form(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'unit_id' => $unit->getKey(),
                'roles' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();

        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'unit_id' => $unit->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Jane Smith', $user->name);
        $this->assertSame('jane.smith@example.com', $user->email);
        $this->assertSame($unit->getKey(), $user->unit_id);
    }
}
