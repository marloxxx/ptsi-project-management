<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Services\TicketServiceInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketDependency;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Tests\TestCase;

class TicketParentChildTest extends TestCase
{
    use RefreshDatabase;

    private TicketServiceInterface $ticketService;

    private Project $project;

    private TicketStatus $statusOpen;

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

        $this->priority = TicketPriority::factory()->create([
            'name' => 'High',
        ]);

        $this->creator = User::factory()->create();
        Auth::login($this->creator);
    }

    public function test_it_creates_ticket_with_parent(): void
    {
        $parentTicket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Parent Task',
            'issue_type' => 'Task',
        ]);

        $childTicket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Sub-task',
            'issue_type' => 'Task',
            'parent_id' => $parentTicket->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $childTicket);
        $this->assertSame($parentTicket->id, (int) $childTicket->parent_id);
        $this->assertTrue($parentTicket->children()->where('id', $childTicket->id)->exists());
    }

    public function test_it_prevents_circular_parent_reference(): void
    {
        $parentTicket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Parent Task',
            'issue_type' => 'Task',
        ]);

        $childTicket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Sub-task',
            'issue_type' => 'Task',
            'parent_id' => $parentTicket->id,
        ]);

        // Try to set parent ticket as child of its child (circular reference)
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular reference detected');

        $this->ticketService->update($parentTicket->id, [
            'parent_id' => $childTicket->id,
        ]);
    }

    public function test_it_prevents_self_parent_reference(): void
    {
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Task',
            'issue_type' => 'Task',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A ticket cannot be its own parent');

        $this->ticketService->update($ticket->id, [
            'parent_id' => $ticket->id,
        ]);
    }

    public function test_it_prevents_parent_from_different_project(): void
    {
        $otherProject = Project::factory()->create();
        $otherStatus = TicketStatus::factory()
            ->for($otherProject)
            ->create([
                'name' => 'Open',
                'is_completed' => false,
                'sort_order' => 1,
            ]);

        $otherTicket = $this->ticketService->create([
            'project_id' => $otherProject->id,
            'ticket_status_id' => $otherStatus->id,
            'priority_id' => $this->priority->id,
            'name' => 'Other Project Task',
            'issue_type' => 'Task',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parent ticket must belong to the same project');

        $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Task',
            'issue_type' => 'Task',
            'parent_id' => $otherTicket->id,
        ]);
    }

    public function test_it_prevents_deletion_of_ticket_with_children(): void
    {
        $parentTicket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Parent Task',
            'issue_type' => 'Task',
        ]);

        $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Sub-task',
            'issue_type' => 'Task',
            'parent_id' => $parentTicket->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete ticket with sub-tasks');

        $this->ticketService->delete($parentTicket->id);
    }

    public function test_it_creates_ticket_dependency(): void
    {
        $ticket1 = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Ticket 1',
            'issue_type' => 'Task',
        ]);

        $ticket2 = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Ticket 2',
            'issue_type' => 'Task',
        ]);

        $dependency = TicketDependency::create([
            'ticket_id' => $ticket1->id,
            'depends_on_ticket_id' => $ticket2->id,
            'type' => 'blocks',
        ]);

        $this->assertDatabaseHas('ticket_dependencies', [
            'id' => $dependency->id,
            'ticket_id' => $ticket1->id,
            'depends_on_ticket_id' => $ticket2->id,
            'type' => 'blocks',
        ]);

        $this->assertTrue($ticket1->dependencies()->where('depends_on_ticket_id', $ticket2->id)->exists());
        $this->assertTrue($ticket2->dependents()->where('ticket_id', $ticket1->id)->exists());
    }

    public function test_it_prevents_deletion_of_ticket_that_blocks_others(): void
    {
        $ticket1 = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Ticket 1',
            'issue_type' => 'Task',
        ]);

        $ticket2 = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Ticket 2',
            'issue_type' => 'Task',
        ]);

        TicketDependency::create([
            'ticket_id' => $ticket1->id,
            'depends_on_ticket_id' => $ticket2->id,
            'type' => 'blocks',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete ticket that is blocking other tickets');

        $this->ticketService->delete($ticket2->id);
    }

    public function test_ticket_model_has_issue_type(): void
    {
        $ticket = $this->ticketService->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->statusOpen->id,
            'priority_id' => $this->priority->id,
            'name' => 'Bug Report',
            'issue_type' => 'Bug',
        ]);

        $this->assertSame('Bug', $ticket->issue_type);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'issue_type' => 'Bug',
        ]);
    }
}
