<?php

namespace Tests\Feature\Livewire;

use App\Livewire\External\Dashboard;
use App\Models\ExternalAccessToken;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalPortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $project = Project::factory()->create();
        $token = ExternalAccessToken::factory()->for($project)->create();

        Livewire::test(Dashboard::class, ['token' => $token->access_token])
            ->assertRedirect(route('external.login', ['token' => $token->access_token]));
    }

    public function test_dashboard_displays_project_metrics_and_tickets(): void
    {
        $project = Project::factory()->create();
        $statusTodo = TicketStatus::factory()->for($project)->create([
            'name' => 'Todo',
            'is_completed' => false,
            'sort_order' => 0,
        ]);
        $statusDone = TicketStatus::factory()->for($project)->create([
            'name' => 'Done',
            'is_completed' => true,
            'sort_order' => 1,
        ]);
        $priorityHigh = TicketPriority::factory()->create(['name' => 'High']);
        $priorityLow = TicketPriority::factory()->create(['name' => 'Low']);

        $creator = \App\Models\User::factory()->create();

        $openTicket = Ticket::factory()
            ->for($project)
            ->for($statusTodo, 'status')
            ->for($priorityHigh, 'priority')
            ->create([
                'created_by' => $creator->getKey(),
                'due_date' => now()->addDays(3),
            ]);

        $completedTicket = Ticket::factory()
            ->for($project)
            ->for($statusDone, 'status')
            ->for($priorityLow, 'priority')
            ->create([
                'created_by' => $creator->getKey(),
                'due_date' => now()->subDay(),
                'updated_at' => now(),
            ]);

        $completedTicket->assignees()->attach($creator->getKey());

        TicketHistory::factory()
            ->for($openTicket)
            ->create([
                'from_ticket_status_id' => $statusTodo->getKey(),
                'to_ticket_status_id' => $statusDone->getKey(),
                'user_id' => $creator->getKey(),
            ]);

        $token = ExternalAccessToken::factory()
            ->for($project)
            ->create([
                'password' => Hash::make('secret-pass'),
            ]);

        session([
            "external_portal_authenticated_{$token->access_token}" => true,
            "external_portal_project_{$token->access_token}" => $project->getKey(),
        ]);

        Livewire::test(Dashboard::class, ['token' => $token->access_token])
            ->assertSet('summary.total', 2)
            ->assertSee($project->name)
            ->assertSee($openTicket->uuid)
            ->assertSee('High')
            ->assertSee('Todo')
            ->assertSee('Recent Activity');
    }
}
