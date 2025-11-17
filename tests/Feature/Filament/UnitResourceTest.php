<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitResourceTest extends TestCase
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

    public function test_staff_cannot_access_unit_creation(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('filament.admin.resources.units.create'))
            ->assertForbidden();
    }

    public function test_admin_can_view_unit_listing(): void
    {
        $this->actingAsRole('admin');

        $this->get(route('filament.admin.resources.units.index'))
            ->assertOk()
            ->assertSee('Units');
    }

    public function test_admin_can_create_unit_via_filament_form(): void
    {
        $this->actingAsRole('admin');

        Livewire::test(CreateUnit::class)
            ->fillForm([
                'name' => 'Direktorat Operasional',
                'code' => 'DO-01',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('units', [
            'name' => 'Direktorat Operasional',
            'code' => 'DO-01',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_unit(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create([
            'name' => 'Direktorat Operasional',
            'code' => 'DO-01',
        ]);

        Livewire::test(EditUnit::class, ['record' => $unit->getKey()])
            ->fillForm([
                'name' => 'Direktorat Operasional Updated',
                'code' => 'DO-02',
                'status' => 'inactive',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $unit->refresh();

        $this->assertSame('Direktorat Operasional Updated', $unit->name);
        $this->assertSame('DO-02', $unit->code);
        $this->assertSame('inactive', $unit->status);
    }
}
