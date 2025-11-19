<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Repositories\ProjectWorkflowRepositoryInterface;
use App\Domain\Services\TicketServiceInterface;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Tests\TestCase;

class WorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    private TicketServiceInterface $ticketService;

    private ProjectWorkflowRepositoryInterface $workflowRepository;

    private Project $project;

    private TicketStatus $statusTodo;

    private TicketStatus $statusInProgress;

    private TicketStatus $statusDone;

    private TicketPriority $priority;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = $this->app->make(TicketServiceInterface::class);
        $this->workflowRepository = $this->app->make(ProjectWorkflowRepositoryInterface::class);

        $this->project = Project::factory()->create();

        $this->statusTodo = TicketStatus::factory()
            ->for($this->project)
            ->create([
                'name' => 'Todo',
                'is_completed' => false,
                'sort_order' => 1,
            ]);

        $this->statusInProgress = TicketStatus::factory()
            ->for($this->project)
            ->create([
                'name' => 'In Progress',
                'is_completed' => false,
                'sort_order' => 2,
            ]);

        $this->statusDone = TicketStatus::factory()
            ->for($this->project)
            ->create([
                'name' => 'Done',
                'is_completed' => true,
                'sort_order' => 3,
            ]);

        $this->priority = TicketPriority::factory()->create([
            'name' => 'High',
        ]);

        $this->creator = User::factory()->create();
        Auth::login($this->creator);
    }

    public function test_it_allows_transitions_when_no_workflow_defined(): void
    {
        // No workflow defined - should allow all transitions (backward compatible)
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusTodo->id,
            'priority_id' => $this->priority->id,
            'name' => 'Test Ticket',
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);

        // Should allow any transition when no workflow
        $this->ticketService->changeStatus($ticket->id, $this->statusDone->id);

        $ticket->refresh();
        $this->assertEquals($this->statusDone->id, $ticket->ticket_status_id);
    }

    public function test_it_allows_configured_transitions(): void
    {
        // Create workflow: Todo -> In Progress -> Done
        $this->workflowRepository->createOrUpdate($this->project, [
            'definition' => [
                'initial_statuses' => [$this->statusTodo->id],
                'transitions' => [
                    (string) $this->statusTodo->id => [$this->statusInProgress->id],
                    (string) $this->statusInProgress->id => [$this->statusDone->id],
                ],
            ],
        ]);

        // Create ticket with initial status
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusTodo->id,
            'priority_id' => $this->priority->id,
            'name' => 'Test Ticket',
        ]);

        // Allowed: Todo -> In Progress
        $this->ticketService->changeStatus($ticket->id, $this->statusInProgress->id);
        $ticket->refresh();
        $this->assertEquals($this->statusInProgress->id, $ticket->ticket_status_id);

        // Allowed: In Progress -> Done
        $this->ticketService->changeStatus($ticket->id, $this->statusDone->id);
        $ticket->refresh();
        $this->assertEquals($this->statusDone->id, $ticket->ticket_status_id);
    }

    public function test_it_blocks_illegal_transitions(): void
    {
        // Create workflow: Todo -> In Progress -> Done (no direct Todo -> Done)
        $this->workflowRepository->createOrUpdate($this->project, [
            'definition' => [
                'initial_statuses' => [$this->statusTodo->id],
                'transitions' => [
                    (string) $this->statusTodo->id => [$this->statusInProgress->id],
                    (string) $this->statusInProgress->id => [$this->statusDone->id],
                ],
            ],
        ]);

        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusTodo->id,
            'priority_id' => $this->priority->id,
            'name' => 'Test Ticket',
        ]);

        // Blocked: Todo -> Done (skipping In Progress)
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transition from "Todo" to "Done" is not allowed by the project workflow.');

        $this->ticketService->changeStatus($ticket->id, $this->statusDone->id);
    }

    public function test_it_blocks_illegal_initial_status(): void
    {
        // Create workflow: only Todo is allowed as initial status
        $this->workflowRepository->createOrUpdate($this->project, [
            'definition' => [
                'initial_statuses' => [$this->statusTodo->id],
                'transitions' => [
                    (string) $this->statusTodo->id => [$this->statusInProgress->id],
                ],
            ],
        ]);

        // Blocked: creating ticket with In Progress status
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transition from "Initial" to "In Progress" is not allowed by the project workflow.');

        $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusInProgress->id,
            'priority_id' => $this->priority->id,
            'name' => 'Test Ticket',
        ]);
    }

    public function test_it_allows_update_with_valid_transition(): void
    {
        $this->workflowRepository->createOrUpdate($this->project, [
            'definition' => [
                'initial_statuses' => [$this->statusTodo->id],
                'transitions' => [
                    (string) $this->statusTodo->id => [$this->statusInProgress->id],
                ],
            ],
        ]);

        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusTodo->id,
            'priority_id' => $this->priority->id,
            'name' => 'Test Ticket',
        ]);

        // Allowed: Update with valid transition
        $this->ticketService->update($ticket->id, [
            'ticket_status_id' => $this->statusInProgress->id,
            'name' => 'Updated Ticket',
        ]);

        $ticket->refresh();
        $this->assertEquals($this->statusInProgress->id, $ticket->ticket_status_id);
    }

    public function test_it_blocks_update_with_invalid_transition(): void
    {
        $this->workflowRepository->createOrUpdate($this->project, [
            'definition' => [
                'initial_statuses' => [$this->statusTodo->id],
                'transitions' => [
                    (string) $this->statusTodo->id => [$this->statusInProgress->id],
                ],
            ],
        ]);

        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusTodo->id,
            'priority_id' => $this->priority->id,
            'name' => 'Test Ticket',
        ]);

        // Blocked: Update with invalid transition
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transition from "Todo" to "Done" is not allowed by the project workflow.');

        $this->ticketService->update($ticket->id, [
            'ticket_status_id' => $this->statusDone->id,
            'name' => 'Updated Ticket',
        ]);
    }

    public function test_project_workflow_model_validates_transitions(): void
    {
        $workflow = ProjectWorkflow::factory()
            ->for($this->project)
            ->withTransitions([
                $this->statusTodo->id,
                $this->statusInProgress->id,
                $this->statusDone->id,
            ])
            ->create();

        // Test allowed transitions
        $this->assertTrue($workflow->isTransitionAllowed(null, $this->statusTodo->id));
        $this->assertTrue($workflow->isTransitionAllowed($this->statusTodo->id, $this->statusInProgress->id));
        $this->assertTrue($workflow->isTransitionAllowed($this->statusInProgress->id, $this->statusDone->id));

        // Test blocked transitions
        $this->assertFalse($workflow->isTransitionAllowed($this->statusTodo->id, $this->statusDone->id));
        $this->assertFalse($workflow->isTransitionAllowed(null, $this->statusInProgress->id));

        // Test getAllowedTargetStatuses
        $allowedFromTodo = $workflow->getAllowedTargetStatuses($this->statusTodo->id);
        $this->assertContains($this->statusInProgress->id, $allowedFromTodo);
        $this->assertNotContains($this->statusDone->id, $allowedFromTodo);

        $allowedInitial = $workflow->getAllowedTargetStatuses(null);
        $this->assertContains($this->statusTodo->id, $allowedInitial);
    }
}
