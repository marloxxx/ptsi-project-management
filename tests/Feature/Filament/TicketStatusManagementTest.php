<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\TicketStatusesRelationManager;
use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketStatusManagementTest extends TestCase
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

    public function test_admin_can_create_ticket_status_via_relation_manager(): void
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

        Livewire::test(TicketStatusesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make(CreateAction::class)->table())
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'name' => 'In Progress',
                    'color' => '#10B981',
                    'is_completed' => false,
                    'sort_order' => 1,
                ],
            )
            ->assertNotified();

        $this->assertDatabaseHas('ticket_statuses', [
            'project_id' => $project->getKey(),
            'name' => 'In Progress',
            'color' => '#10B981',
            'is_completed' => false,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_update_ticket_status(): void
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

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        // Get fresh status instance with project and members loaded
        /** @var TicketStatus $status */
        $status = TicketStatus::with('project.members')->findOrFail($status->getKey());

        Livewire::test(TicketStatusesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('edit')->table($status))
            ->callAction(
                TestAction::make('edit')->table($status),
                data: [
                    'name' => 'Updated Status Name',
                    'color' => '#EF4444',
                    'is_completed' => true,
                ],
            )
            ->assertNotified();

        $status->refresh();

        $this->assertSame('Updated Status Name', $status->name);
        $this->assertSame('#EF4444', $status->color);
        $this->assertTrue($status->is_completed);
    }

    public function test_admin_can_delete_ticket_status(): void
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

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        // Get fresh status instance with project and members loaded
        /** @var TicketStatus $status */
        $status = TicketStatus::with('project.members')->findOrFail($status->getKey());

        Livewire::test(TicketStatusesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('delete')->table($status))
            ->callAction(
                TestAction::make('delete')->table($status),
            )
            ->assertNotified();

        $this->assertDatabaseMissing('ticket_statuses', [
            'id' => $status->getKey(),
        ]);
    }
}
