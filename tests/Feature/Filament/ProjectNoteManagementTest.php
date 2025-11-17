<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\ProjectNotesRelationManager;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectNoteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

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

    public function test_admin_can_create_project_note_via_relation_manager(): void
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

        Livewire::test(ProjectNotesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make(CreateAction::class)->table())
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'title' => 'Project Note 1',
                    'body' => 'This is a test note',
                    'note_date' => now()->toDateString(),
                ],
            )
            ->assertNotified();

        $this->assertDatabaseHas('project_notes', [
            'project_id' => $project->getKey(),
            'title' => 'Project Note 1',
            'created_by' => $admin->getKey(),
        ]);
    }

    public function test_admin_can_update_project_note(): void
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

        $note = ProjectNote::factory()
            ->for($project)
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Get fresh note instance with project and members loaded
        /** @var ProjectNote $note */
        $note = ProjectNote::with('project.members')->findOrFail($note->getKey());

        Livewire::test(ProjectNotesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('edit')->table($note))
            ->callAction(
                TestAction::make('edit')->table($note),
                data: [
                    'title' => 'Updated Note Title',
                    'body' => 'Updated note body',
                ],
            )
            ->assertNotified();

        $note->refresh();

        $this->assertSame('Updated Note Title', $note->title);
        $this->assertSame('Updated note body', $note->body);
    }

    public function test_admin_can_delete_project_note(): void
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

        $note = ProjectNote::factory()
            ->for($project)
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Get fresh note instance with project and members loaded
        /** @var ProjectNote $note */
        $note = ProjectNote::with('project.members')->findOrFail($note->getKey());

        Livewire::test(ProjectNotesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('delete')->table($note))
            ->callAction(
                TestAction::make('delete')->table($note),
            )
            ->assertNotified();

        $this->assertDatabaseMissing('project_notes', [
            'id' => $note->getKey(),
        ]);
    }

    public function test_non_project_member_cannot_create_project_note(): void
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

        Livewire::test(ProjectNotesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make(CreateAction::class)->table());
    }

    public function test_non_project_member_cannot_edit_project_note(): void
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

        $note = ProjectNote::factory()
            ->for($project)
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        /** @var ProjectNote $note */
        $note = ProjectNote::with('project.members')->findOrFail($note->getKey());

        Livewire::test(ProjectNotesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make('edit')->table($note));
    }

    public function test_non_project_member_cannot_delete_project_note(): void
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

        $note = ProjectNote::factory()
            ->for($project)
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        /** @var ProjectNote $note */
        $note = ProjectNote::with('project.members')->findOrFail($note->getKey());

        Livewire::test(ProjectNotesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make('delete')->table($note));
    }
}
