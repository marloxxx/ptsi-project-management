<?php

use App\Domain\Services\ProjectServiceInterface;
use App\Domain\Services\TicketServiceInterface;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class)->group('services');

test('ticket service creates ticket with history and assignees', function (): void {
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

    actingAs($owner);

    $ticket = $ticketService->create([
        'project_id' => $project->id,
        'ticket_status_id' => $initialStatus->id,
        'name' => 'Configure SSO',
        'description' => 'Configure SSO for external partner',
        'start_date' => Carbon::today()->toDateString(),
        'due_date' => Carbon::tomorrow()->toDateString(),
    ], [$assignee->id]);

    expect($ticket)->toBeInstanceOf(Ticket::class);

    assertDatabaseHas('tickets', [
        'project_id' => $project->id,
        'name' => 'Configure SSO',
    ]);

    expect($ticket->assignees->pluck('id'))->toContain($assignee->id);
    expect(TicketHistory::where('ticket_id', $ticket->id)->count())->toBe(1);
});

test('ticket service changes status and manages comments', function (): void {
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

    actingAs($user);

    $ticket = $ticketService->create([
        'project_id' => $project->id,
        'ticket_status_id' => $initialStatus->id,
        'name' => 'Implement dashboard widgets',
    ], [$user->id]);

    $ticketService->changeStatus($ticket->id, $reviewStatus->id, 'Ready for review');

    $updatedTicket = $ticket->fresh();

    expect($updatedTicket->ticket_status_id)->toBe($reviewStatus->id);

    assertDatabaseHas('ticket_histories', [
        'ticket_id' => $ticket->id,
        'to_ticket_status_id' => $reviewStatus->id,
        'note' => 'Ready for review',
    ]);

    $comment = $ticketService->addComment($ticket->id, [
        'user_id' => $user->id,
        'body' => 'Please verify KPI widget export.',
        'is_internal' => false,
    ]);

    assertDatabaseHas('ticket_comments', [
        'id' => $comment->id,
        'body' => 'Please verify KPI widget export.',
    ]);

    expect($ticketService->deleteComment($comment->id))->toBeTrue();

    assertDatabaseMissing('ticket_comments', [
        'id' => $comment->id,
    ]);
});
