<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Domain\Services\TicketServiceInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
use App\Models\User;
use App\Notifications\TicketCommentAdded;
use App\Notifications\TicketCommentUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private TicketServiceInterface $ticketService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketServiceInterface::class);
    }

    public function test_new_comment_notifies_ticket_participants(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $commenter = User::factory()->create();

        $project = Project::factory()->create();
        $status = TicketStatus::factory()->create(['project_id' => $project->getKey()]);

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->create([
                'created_by' => $creator->getKey(),
            ]);

        $ticket->assignees()->attach($assignee->getKey());

        $this->actingAs($commenter);

        $this->ticketService->addComment($ticket->getKey(), [
            'body' => 'Important update to share with everyone.',
        ]);

        Notification::assertSentTo(
            [$creator, $assignee],
            TicketCommentAdded::class
        );

        Notification::assertNotSentTo(
            $commenter,
            TicketCommentAdded::class
        );
    }

    public function test_comment_update_notifies_existing_watchers(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $commentAuthor = User::factory()->create();
        $otherParticipant = User::factory()->create();

        $project = Project::factory()->create();
        $status = TicketStatus::factory()->create(['project_id' => $project->getKey()]);

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->create([
                'created_by' => $creator->getKey(),
            ]);

        $ticket->assignees()->attach($assignee->getKey());

        TicketComment::factory()->for($ticket)->create([
            'user_id' => $otherParticipant->getKey(),
            'body' => 'Initial feedback from QA.',
        ]);

        $comment = TicketComment::factory()->for($ticket)->create([
            'user_id' => $commentAuthor->getKey(),
            'body' => 'Original comment payload.',
        ]);

        $this->actingAs($commentAuthor);

        $this->ticketService->updateComment($comment->getKey(), [
            'body' => 'Updated comment after refinement.',
        ]);

        Notification::assertSentTo(
            [$creator, $assignee, $otherParticipant],
            TicketCommentUpdated::class
        );

        Notification::assertNotSentTo(
            $commentAuthor,
            TicketCommentUpdated::class
        );
    }
}
