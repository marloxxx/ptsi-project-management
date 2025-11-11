<?php

namespace Tests\Feature\Services;

use App\Domain\Services\ProjectServiceInterface;
use App\Domain\Services\TicketServiceInterface;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_service_creates_ticket_with_history_and_assignees(): void
    {
        /** @var ProjectServiceInterface $projectService */
        $projectService = app(ProjectServiceInterface::class);
        /** @var TicketServiceInterface $ticketService */
        $ticketService = app(TicketServiceInterface::class);

        $owner = User::factory()->create();
        $assignee = User::factory()->create();

        $project = $projectService->create([
            'name' => 'Support Rollout',
            'ticket_prefix' => 'SUP',
        ], [$owner->id]);

        /** @var TicketStatus $initialStatus */
        $initialStatus = $project->ticketStatuses()->first();

        $this->actingAs($owner);

        $ticket = $ticketService->create([
            'project_id' => $project->id,
            'ticket_status_id' => $initialStatus->id,
            'name' => 'Configure SSO',
            'description' => 'Configure SSO for external partner',
            'start_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::tomorrow()->toDateString(),
        ], [$assignee->id]);

        $this->assertInstanceOf(Ticket::class, $ticket);

        $this->assertDatabaseHas('tickets', [
            'project_id' => $project->id,
            'name' => 'Configure SSO',
        ]);

        $this->assertContains($assignee->id, $ticket->assignees->pluck('id')->all());
        $this->assertSame(1, TicketHistory::where('ticket_id', $ticket->id)->count());
    }

    public function test_ticket_service_changes_status_and_manages_comments(): void
    {
        /** @var ProjectServiceInterface $projectService */
        $projectService = app(ProjectServiceInterface::class);
        /** @var TicketServiceInterface $ticketService */
        $ticketService = app(TicketServiceInterface::class);

        $user = User::factory()->create();

        $project = $projectService->create([
            'name' => 'Client Portal',
            'ticket_prefix' => 'CLP',
        ]);

        $initialStatus = $project->ticketStatuses()->first();

        $reviewStatus = $projectService->addStatus($project->id, [
            'name' => 'QA Review',
            'color' => '#F59E0B',
            'is_completed' => false,
        ]);

        $this->actingAs($user);

        $ticket = $ticketService->create([
            'project_id' => $project->id,
            'ticket_status_id' => $initialStatus->id,
            'name' => 'Implement dashboard widgets',
        ], [$user->id]);

        $ticketService->changeStatus($ticket->id, $reviewStatus->id, 'Ready for review');

        $updatedTicket = $ticket->fresh();

        $this->assertSame($reviewStatus->id, $updatedTicket->ticket_status_id);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'to_ticket_status_id' => $reviewStatus->id,
            'note' => 'Ready for review',
        ]);

        $comment = $ticketService->addComment($ticket->id, [
            'user_id' => $user->id,
            'body' => 'Please verify KPI widget export.',
            'is_internal' => false,
        ]);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => 'Please verify KPI widget export.',
        ]);

        $this->assertTrue($ticketService->deleteComment($comment->id));

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->id,
        ]);
    }
}
