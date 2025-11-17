<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Services\GlobalSearchServiceInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private GlobalSearchServiceInterface $globalSearchService;

    private User $user;

    private Project $project;

    private TicketStatus $status;

    private TicketPriority $priority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globalSearchService = $this->app->make(GlobalSearchServiceInterface::class);

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->status = TicketStatus::factory()->for($this->project)->create();
        $this->priority = TicketPriority::factory()->create();

        // Add user to project
        $this->project->members()->attach($this->user->id);

        Auth::login($this->user);
    }

    public function test_search_returns_empty_when_user_not_authenticated(): void
    {
        Auth::logout();

        $results = $this->globalSearchService->search('test');

        $this->assertEmpty($results['tickets']);
        $this->assertEmpty($results['comments']);
    }

    public function test_search_returns_empty_when_query_is_empty(): void
    {
        $results = $this->globalSearchService->search('   ');

        $this->assertEmpty($results['tickets']);
        $this->assertEmpty($results['comments']);
    }

    public function test_search_finds_tickets_by_name(): void
    {
        $ticket = Ticket::factory()->for($this->project)->create([
            'name' => 'Test Ticket Name',
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('Test Ticket');

        $this->assertCount(1, $results['tickets']);
        $this->assertEquals($ticket->id, $results['tickets']->first()->id);
    }

    public function test_search_finds_tickets_by_description(): void
    {
        $ticket = Ticket::factory()->for($this->project)->create([
            'name' => 'Some Name',
            'description' => 'This is a test description',
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('test description');

        $this->assertCount(1, $results['tickets']);
        $this->assertEquals($ticket->id, $results['tickets']->first()->id);
    }

    public function test_search_finds_tickets_by_uuid(): void
    {
        $ticket = Ticket::factory()->for($this->project)->create([
            'uuid' => 'TEST-123456',
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('TEST-123456');

        $this->assertCount(1, $results['tickets']);
        $this->assertEquals($ticket->id, $results['tickets']->first()->id);
    }

    public function test_search_finds_comments_by_body(): void
    {
        $ticket = Ticket::factory()->for($this->project)->create([
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        $comment = TicketComment::factory()->for($ticket)->create([
            'body' => 'This is a test comment',
            'user_id' => $this->user->id,
        ]);

        $results = $this->globalSearchService->search('test comment');

        $this->assertCount(1, $results['comments']);
        $this->assertEquals($comment->id, $results['comments']->first()->id);
    }

    public function test_search_respects_project_scope(): void
    {
        $otherProject = Project::factory()->create();
        $otherStatus = TicketStatus::factory()->for($otherProject)->create();

        // Ticket in user's project
        $ticket1 = Ticket::factory()->for($this->project)->create([
            'name' => 'Test Ticket 1',
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        // Ticket in other project (user is not a member)
        Ticket::factory()->for($otherProject)->create([
            'name' => 'Test Ticket 2',
            'ticket_status_id' => $otherStatus->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('Test Ticket');

        $this->assertCount(1, $results['tickets']);
        $this->assertEquals($ticket1->id, $results['tickets']->first()->id);
    }

    public function test_search_filters_by_project_id(): void
    {
        $project2 = Project::factory()->create();
        $project2->members()->attach($this->user->id);
        $status2 = TicketStatus::factory()->for($project2)->create();

        $ticket1 = Ticket::factory()->for($this->project)->create([
            'name' => 'Test Ticket',
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        Ticket::factory()->for($project2)->create([
            'name' => 'Test Ticket',
            'ticket_status_id' => $status2->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('Test Ticket', $this->project->id);

        $this->assertCount(1, $results['tickets']);
        $this->assertEquals($ticket1->id, $results['tickets']->first()->id);
    }

    public function test_search_applies_additional_filters(): void
    {
        $status2 = TicketStatus::factory()->for($this->project)->create();

        $ticket1 = Ticket::factory()->for($this->project)->create([
            'name' => 'Test Ticket',
            'ticket_status_id' => $this->status->id,
            'priority_id' => $this->priority->id,
        ]);

        Ticket::factory()->for($this->project)->create([
            'name' => 'Test Ticket',
            'ticket_status_id' => $status2->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('Test Ticket', null, [
            'status_id' => $this->status->id,
        ]);

        $this->assertCount(1, $results['tickets']);
        $this->assertEquals($ticket1->id, $results['tickets']->first()->id);
    }

    public function test_search_does_not_return_tickets_user_cannot_access(): void
    {
        $otherProject = Project::factory()->create();
        $otherStatus = TicketStatus::factory()->for($otherProject)->create();

        // Ticket in project where user is not a member
        Ticket::factory()->for($otherProject)->create([
            'name' => 'Test Ticket',
            'ticket_status_id' => $otherStatus->id,
            'priority_id' => $this->priority->id,
        ]);

        $results = $this->globalSearchService->search('Test Ticket');

        $this->assertEmpty($results['tickets']);
    }
}
