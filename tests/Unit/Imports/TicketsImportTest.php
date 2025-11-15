<?php

declare(strict_types=1);

namespace Tests\Unit\Imports;

use App\Imports\TicketsImport;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TicketsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tickets_from_collection(): void
    {
        $project = Project::factory()->create();
        $status = TicketStatus::factory()->for($project)->create();
        $priority = TicketPriority::factory()->create();
        $assignee = User::factory()->create();
        $creator = User::factory()->create();
        auth()->login($creator);

        $import = new TicketsImport;

        $import->collection(new Collection([
            [
                'project_id' => $project->id,
                'ticket_status_id' => $status->id,
                'priority_id' => $priority->id,
                'name' => 'Imported via test',
                'description' => 'Description from spreadsheet',
                'assignee_ids' => (string) $assignee->id,
            ],
        ]));

        $this->assertDatabaseHas('tickets', [
            'name' => 'Imported via test',
            'project_id' => $project->id,
        ]);

        $ticket = Ticket::query()->where('name', 'Imported via test')->firstOrFail();

        $this->assertSame(1, $ticket->assignees()->count());
    }

    public function test_it_updates_existing_tickets_when_allowed(): void
    {
        $project = Project::factory()->create();
        $status = TicketStatus::factory()->for($project)->create();
        $priority = TicketPriority::factory()->create();
        $creator = User::factory()->create();
        auth()->login($creator);

        $ticket = Ticket::factory()->create([
            'project_id' => $project->id,
            'ticket_status_id' => $status->id,
            'priority_id' => $priority->id,
            'name' => 'Original name',
        ]);

        $import = new TicketsImport(true);

        $import->collection(new Collection([
            [
                'id' => $ticket->id,
                'project_id' => $project->id,
                'ticket_status_id' => $status->id,
                'priority_id' => $priority->id,
                'name' => 'Updated name',
            ],
        ]));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'name' => 'Updated name',
        ]);
    }
}
