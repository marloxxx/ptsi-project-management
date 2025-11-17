<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\EpicsRelationManager;
use App\Models\Epic;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EpicManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        Filament::setCurrentPanel('admin');
        $this->seed(RbacSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Clear permission cache to ensure fresh permissions
        $user->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_create_epic_via_relation_manager(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        // Get fresh project instance with members loaded
        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(EpicsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make(CreateAction::class)->table())
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'name' => 'Epic 1',
                    'description' => 'Complete core features',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays(30)->toDateString(),
                    'sort_order' => 1,
                ],
            )
            ->assertNotified();

        $this->assertDatabaseHas('epics', [
            'project_id' => $project->getKey(),
            'name' => 'Epic 1',
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_update_epic(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        // Get fresh project instance with members loaded
        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        $epic = Epic::factory()
            ->for($project)
            ->create();

        // Get fresh epic instance with project and members loaded
        /** @var Epic $epic */
        $epic = Epic::with('project.members')->findOrFail($epic->getKey());

        Livewire::test(EpicsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('edit')->table($epic))
            ->callAction(
                TestAction::make('edit')->table($epic),
                data: [
                    'name' => 'Updated Epic Name',
                    'description' => 'Updated description',
                ],
            )
            ->assertNotified();

        $epic->refresh();

        $this->assertSame('Updated Epic Name', $epic->name);
        $this->assertSame('Updated description', $epic->description);
    }

    public function test_admin_can_delete_epic(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        // Get fresh project instance with members loaded
        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        $epic = Epic::factory()
            ->for($project)
            ->create();

        // Get fresh epic instance with project and members loaded
        /** @var Epic $epic */
        $epic = Epic::with('project.members')->findOrFail($epic->getKey());

        Livewire::test(EpicsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('delete')->table($epic))
            ->callAction(
                TestAction::make('delete')->table($epic),
            )
            ->assertNotified();

        $this->assertDatabaseMissing('epics', [
            'id' => $epic->getKey(),
        ]);
    }

    public function test_non_project_member_cannot_create_epic(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        // Clear permission cache
        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);
        // nonMember is NOT a project member

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        Livewire::test(EpicsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make(CreateAction::class)->table());
    }

    public function test_non_project_member_cannot_edit_epic(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        // Clear permission cache
        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $epic = Epic::factory()
            ->for($project)
            ->create();

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        /** @var Epic $epic */
        $epic = Epic::with('project.members')->findOrFail($epic->getKey());

        Livewire::test(EpicsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make('edit')->table($epic));
    }

    public function test_non_project_member_cannot_delete_epic(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        // Clear permission cache
        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $epic = Epic::factory()
            ->for($project)
            ->create();

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        /** @var Epic $epic */
        $epic = Epic::with('project.members')->findOrFail($epic->getKey());

        Livewire::test(EpicsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make('delete')->table($epic));
    }
}
