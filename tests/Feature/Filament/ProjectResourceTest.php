<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
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

        $this->actingAs($user);

        return $user;
    }

    public function test_staff_cannot_access_project_creation(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('filament.admin.resources.projects.create'))
            ->assertForbidden();
    }

    public function test_admin_can_view_project_listing(): void
    {
        $this->actingAsRole('admin');

        $this->get(route('filament.admin.resources.projects.index'))
            ->assertOk()
            ->assertSee('Projects');
    }

    public function test_admin_can_create_project_via_filament_form(): void
    {
        $admin = $this->actingAsRole('admin');

        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Platform Revamp',
                'description' => 'Upgrade the customer platform experience.',
                'ticket_prefix' => 'REV',
                'color' => '#184980',
                'start_date' => now()->toDateString(),
                'member_ids' => [$admin->getKey()],
                'status_presets' => [
                    ['name' => 'Planning', 'color' => '#2563EB', 'is_completed' => '0'],
                    ['name' => 'Launch', 'color' => '#16A34A', 'is_completed' => '1'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::where('ticket_prefix', 'REV')->with('ticketStatuses', 'members')->first();

        $this->assertNotNull($project);
        $this->assertSame('Platform Revamp', $project->name);
        $this->assertGreaterThanOrEqual(2, $project->ticketStatuses->count());
        $this->assertTrue($project->members->contains('id', $admin->getKey()));
    }

    public function test_admin_can_update_project_and_sync_members(): void
    {
        $admin = $this->actingAsRole('admin');
        $member = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->create([
            'name' => 'Legacy Migration',
            'ticket_prefix' => 'LEG',
            'color' => '#184980',
        ]);

        $project->members()->attach($admin->getKey());

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'name' => 'Legacy Migration 2.0',
                'description' => 'Second phase migration efforts',
                'ticket_prefix' => 'LEG',
                'color' => '#00B0A8',
                'member_ids' => [$member->getKey()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();

        $this->assertSame('Legacy Migration 2.0', $project->name);
        $this->assertSame('#00B0A8', $project->color);
        $this->assertTrue($project->members->contains('id', $member->getKey()));
        $this->assertFalse($project->members->contains('id', $admin->getKey()));
    }

    public function test_admin_can_view_project_details(): void
    {
        $this->actingAsRole('admin');

        $project = Project::factory()->create([
            'name' => 'Test Project',
            'ticket_prefix' => 'TEST',
        ]);

        $this->get(route('filament.admin.resources.projects.view', ['record' => $project->getKey()]))
            ->assertOk();
    }

    public function test_admin_can_delete_project(): void
    {
        $this->actingAsRole('admin');

        $project = Project::factory()->create([
            'name' => 'Test Project',
            'ticket_prefix' => 'TEST',
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('delete')
            ->assertNotified();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->getKey(),
        ]);
    }
}
