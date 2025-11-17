<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Services\SprintServiceInterface;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\SprintsRelationManager;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SprintManagementTest extends TestCase
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

    public function test_admin_can_create_sprint_via_relation_manager(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        // Get fresh project instance with members loaded
        $project = Project::with('members')->findOrFail($project->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make(CreateAction::class)->table())
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'name' => 'Sprint 1',
                    'goal' => 'Complete core features',
                    'state' => 'Planned',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays(14)->toDateString(),
                ],
            )
            ->assertNotified();

        $this->assertDatabaseHas('sprints', [
            'project_id' => $project->getKey(),
            'name' => 'Sprint 1',
            'state' => 'Planned',
            'created_by' => $admin->getKey(),
        ]);
    }

    public function test_admin_can_activate_sprint(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->planned()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callAction(TestAction::make('activate')->table($sprint))
            ->assertNotified();

        $sprint->refresh();

        $this->assertSame('Active', $sprint->state);
        $this->assertNull($sprint->closed_at);
    }

    public function test_activating_sprint_closes_other_active_sprint(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Sprint $activeSprint */
        $activeSprint = Sprint::factory()
            ->for($project)
            ->active()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        /** @var Sprint $newSprint */
        $newSprint = Sprint::factory()
            ->for($project)
            ->planned()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callAction(TestAction::make('activate')->table($newSprint))
            ->assertNotified();

        $activeSprint->refresh();
        $newSprint->refresh();

        $this->assertSame('Closed', $activeSprint->state);
        $this->assertNotNull($activeSprint->closed_at);
        $this->assertSame('Active', $newSprint->state);
    }

    public function test_admin_can_close_sprint(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->active()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callAction(TestAction::make('close')->table($sprint))
            ->assertNotified();

        $sprint->refresh();

        $this->assertSame('Closed', $sprint->state);
        $this->assertNotNull($sprint->closed_at);
    }

    public function test_admin_can_reopen_closed_sprint(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->closed()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callAction(TestAction::make('reopen')->table($sprint))
            ->assertNotified();

        $sprint->refresh();

        $this->assertSame('Active', $sprint->state);
        $this->assertNull($sprint->closed_at);
    }

    public function test_sprint_service_computes_velocity_correctly(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $completedStatus = TicketStatus::factory()
            ->for($project)
            ->create([
                'name' => 'Done',
                'is_completed' => true,
            ]);

        $inProgressStatus = TicketStatus::factory()
            ->for($project)
            ->create([
                'name' => 'In Progress',
                'is_completed' => false,
            ]);

        $priority = TicketPriority::factory()->create();

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->active()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Create completed tickets
        Ticket::factory()
            ->count(3)
            ->for($project)
            ->for($sprint, 'sprint')
            ->create([
                'ticket_status_id' => $completedStatus->getKey(),
                'priority_id' => $priority->getKey(),
                'created_by' => $admin->getKey(),
            ]);

        // Create in-progress tickets
        Ticket::factory()
            ->count(2)
            ->for($project)
            ->for($sprint, 'sprint')
            ->create([
                'ticket_status_id' => $inProgressStatus->getKey(),
                'priority_id' => $priority->getKey(),
                'created_by' => $admin->getKey(),
            ]);

        /** @var SprintServiceInterface $sprintService */
        $sprintService = app(SprintServiceInterface::class);

        $velocity = $sprintService->computeVelocity($sprint);

        $this->assertEquals(3.0, $velocity);
    }

    public function test_sprint_service_computes_burndown_data(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $completedStatus = TicketStatus::factory()
            ->for($project)
            ->create([
                'name' => 'Done',
                'is_completed' => true,
            ]);

        $priority = TicketPriority::factory()->create();

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->active()
            ->create([
                'created_by' => $admin->getKey(),
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(9),
            ]);

        // Create tickets
        Ticket::factory()
            ->count(5)
            ->for($project)
            ->for($sprint, 'sprint')
            ->create([
                'ticket_status_id' => $completedStatus->getKey(),
                'priority_id' => $priority->getKey(),
                'created_by' => $admin->getKey(),
            ]);

        /** @var SprintServiceInterface $sprintService */
        $sprintService = app(SprintServiceInterface::class);

        $burndownData = $sprintService->computeBurndown($sprint);

        $this->assertIsArray($burndownData);
        $this->assertGreaterThan(0, count($burndownData));

        // Check structure
        if (! empty($burndownData)) {
            $firstDay = $burndownData[0];
            $this->assertArrayHasKey('date', $firstDay);
            $this->assertArrayHasKey('remaining', $firstDay);
            $this->assertArrayHasKey('ideal', $firstDay);
        }
    }

    public function test_admin_can_update_sprint(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        // Get fresh project instance with members loaded
        $project = Project::with('members')->findOrFail($project->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->planned()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Get fresh sprint instance with project and members loaded
        $sprint = Sprint::with('project.members')->findOrFail($sprint->getKey());

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('edit')->table($sprint))
            ->callAction(
                TestAction::make('edit')->table($sprint),
                data: [
                    'name' => 'Updated Sprint Name',
                    'goal' => 'Updated goal',
                ],
            )
            ->assertNotified();

        $sprint->refresh();

        $this->assertSame('Updated Sprint Name', $sprint->name);
        $this->assertSame('Updated goal', $sprint->goal);
    }

    public function test_admin_can_delete_sprint(): void
    {
        $admin = $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->members()->attach($admin);

        // Get fresh project instance with members loaded
        $project = Project::with('members')->findOrFail($project->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        /** @var Sprint $sprint */
        $sprint = Sprint::factory()
            ->for($project)
            ->planned()
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Get fresh sprint instance with project and members loaded
        $sprint = Sprint::with('project.members')->findOrFail($sprint->getKey());

        Livewire::test(SprintsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('delete')->table($sprint))
            ->callAction(
                TestAction::make('delete')->table($sprint),
            )
            ->assertNotified();

        $this->assertDatabaseMissing('sprints', [
            'id' => $sprint->getKey(),
        ]);
    }
}
