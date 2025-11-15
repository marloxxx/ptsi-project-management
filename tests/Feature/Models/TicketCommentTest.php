<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_comment_belongs_to_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $comment = TicketComment::factory()->for($ticket)->create();

        $this->assertInstanceOf(Ticket::class, $comment->ticket);
        $this->assertEquals($ticket->id, $comment->ticket->id);
    }

    public function test_ticket_comment_belongs_to_author(): void
    {
        $user = User::factory()->create();
        $comment = TicketComment::factory()->for($user, 'author')->create();

        $this->assertInstanceOf(User::class, $comment->author);
        $this->assertEquals($user->id, $comment->author->id);
    }

    public function test_ticket_comment_casts_is_internal_correctly(): void
    {
        $comment = TicketComment::factory()->create(['is_internal' => true]);

        $this->assertIsBool($comment->is_internal);
        $this->assertTrue($comment->is_internal);
    }

    public function test_ticket_comment_can_be_created_with_factory(): void
    {
        $comment = TicketComment::factory()->create([
            'body' => 'This is a test comment',
            'is_internal' => false,
        ]);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => 'This is a test comment',
            'is_internal' => false,
        ]);
    }

    public function test_ticket_comment_can_be_updated(): void
    {
        $comment = TicketComment::factory()->create(['body' => 'Old comment']);

        $comment->update(['body' => 'Updated comment']);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => 'Updated comment',
        ]);
    }

    public function test_ticket_comment_can_be_deleted(): void
    {
        $comment = TicketComment::factory()->create();

        $comment->delete();

        $this->assertDatabaseMissing('ticket_comments', ['id' => $comment->id]);
    }

    public function test_ticket_comment_internal_flag_defaults_correctly(): void
    {
        $comment = TicketComment::factory()->create(['is_internal' => false]);

        $this->assertFalse($comment->is_internal);
    }
}
