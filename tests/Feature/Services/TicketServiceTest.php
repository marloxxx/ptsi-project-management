<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Domain\Services\TicketServiceInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketServiceInterface $ticketService;

    private Project $project;

    private TicketStatus $statusOpen;

    private TicketStatus $statusDone;

    private TicketPriority $priority;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = $this->app->make(TicketServiceInterface::class);

        $this->project = Project::factory()->create();

        $this->statusOpen = TicketStatus::factory()
            ->for($this->project)
            ->create([
                'name' => 'Open',
                'is_completed' => false,
                'sort_order' => 1,
            ]);

        $this->statusDone = TicketStatus::factory()
            ->for($this->project)
            ->create([
                'name' => 'Done',
                'is_completed' => true,
                'sort_order' => 2,
            ]);

        $this->priority = TicketPriority::factory()->create([
            'name' => 'High',
        ]);

        $this->creator = User::factory()->create();
        Auth::login($this->creator);
    }

    public function test_it_creates_ticket_with_history_and_assignees(): void
    {
        $assigneeOne = User::factory()->create();
        $assigneeTwo = User::factory()->create();

        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Implement new workflow',
            'description' => 'Detailed ticket description',
            'start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ], [$assigneeOne->id, $assigneeTwo->id]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNotNull($ticket->uuid);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Implement new workflow',
        ]);

        $this->assertCount(2, $ticket->assignees);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'from_ticket_status_id' => null,
            'to_ticket_status_id' => $this->statusOpen->id,
        ]);
    }

    public function test_it_updates_ticket_and_records_status_history(): void
    {
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Initial ticket',
            'description' => 'Pending work',
        ]);

        $updated = $this->ticketService->update($ticket->id, [
            'ticket_status_id' => $this->statusDone->id,
            'status_note' => 'Completed successfully',
            'name' => 'Initial ticket',
        ]);

        $this->assertSame($this->statusDone->id, (int) $updated->ticket_status_id);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'from_ticket_status_id' => $this->statusOpen->id,
            'to_ticket_status_id' => $this->statusDone->id,
            'note' => 'Completed successfully',
        ]);
    }

    public function test_it_assigns_additional_users(): void
    {
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Assignment ticket',
        ]);

        $newAssignees = User::factory()->count(3)->create();

        $this->ticketService->assignUsers($ticket->id, $newAssignees->pluck('id')->all());

        $ticket->refresh();

        $this->assertSame(3, $ticket->assignees()->count());
    }

    public function test_it_adds_comments(): void
    {
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Commentable ticket',
        ]);

        $commentAuthor = User::factory()->create();

        $comment = $this->ticketService->addComment($ticket->id, [
            'body' => 'This is an important update.',
            'user_id' => $commentAuthor->id,
        ]);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'ticket_id' => $ticket->id,
            'user_id' => $commentAuthor->id,
            'body' => 'This is an important update.',
        ]);
    }
}
