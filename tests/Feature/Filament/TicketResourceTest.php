<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketResourceTest extends TestCase
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

    public function test_staff_can_access_ticket_creation(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('filament.admin.resources.tickets.create'))
            ->assertOk();
    }

    public function test_admin_can_view_ticket_listing(): void
    {
        $this->actingAsRole('admin');

        $this->get(route('filament.admin.resources.tickets.index'))
            ->assertOk()
            ->assertSee('Tickets');
    }

    public function test_admin_can_create_ticket_via_filament_form(): void
    {
        $admin = $this->actingAsRole('admin');

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'project_id' => $project->getKey(),
                'ticket_status_id' => $status->getKey(),
                'priority_id' => $priority->getKey(),
                'name' => 'Implement user authentication',
                'description' => 'Add login and registration functionality.',
                'assignee_ids' => [$admin->getKey()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ticket = Ticket::where('name', 'Implement user authentication')
            ->with(['project', 'status', 'priority', 'assignees'])
            ->first();

        $this->assertNotNull($ticket);
        $this->assertSame('Implement user authentication', $ticket->name);
        $this->assertSame($project->getKey(), $ticket->project_id);
        $this->assertTrue($ticket->assignees->contains('id', $admin->getKey()));
        $this->assertNotNull($ticket->uuid);
    }

    public function test_admin_can_update_ticket_and_sync_assignees(): void
    {
        $admin = $this->actingAsRole('admin');
        $assignee = User::factory()->create();

        $project = Project::factory()->create();
        $project->members()->attach($admin);
        $project->members()->attach($assignee);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        $ticket->assignees()->attach($admin->getKey());

        Livewire::test(EditTicket::class, ['record' => $ticket->getKey()])
            ->fillForm([
                'name' => 'Updated ticket name',
                'description' => 'Updated description',
                'assignee_ids' => [$assignee->getKey()],
                'due_date' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $ticket->refresh();
        $ticket->load('assignees');

        $this->assertSame('Updated ticket name', $ticket->name);
        $this->assertSame('Updated description', $ticket->description);
        $this->assertTrue($ticket->assignees->contains('id', $assignee->getKey()));
        $this->assertFalse($ticket->assignees->contains('id', $admin->getKey()));
    }

    public function test_admin_can_view_ticket_details(): void
    {
        $admin = $this->actingAsRole('admin');

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        $this->get(route('filament.admin.resources.tickets.view', ['record' => $ticket->getKey()]))
            ->assertOk();
    }

    public function test_admin_can_delete_ticket(): void
    {
        $admin = $this->actingAsRole('admin');

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        Livewire::test(EditTicket::class, ['record' => $ticket->getKey()])
            ->callAction('delete')
            ->assertNotified();

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->getKey(),
        ]);
    }
}
