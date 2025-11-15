<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_history_belongs_to_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $history = TicketHistory::factory()->for($ticket)->create();

        $this->assertInstanceOf(Ticket::class, $history->ticket);
        $this->assertEquals($ticket->id, $history->ticket->id);
    }

    public function test_ticket_history_belongs_to_actor(): void
    {
        $user = User::factory()->create();
        $history = TicketHistory::factory()->for($user, 'actor')->create();

        $this->assertInstanceOf(User::class, $history->actor);
        $this->assertEquals($user->id, $history->actor->id);
    }

    public function test_ticket_history_belongs_to_from_status(): void
    {
        $fromStatus = TicketStatus::factory()->create();
        $history = TicketHistory::factory()->for($fromStatus, 'fromStatus')->create();

        $this->assertInstanceOf(TicketStatus::class, $history->fromStatus);
        $this->assertEquals($fromStatus->id, $history->fromStatus->id);
    }

    public function test_ticket_history_belongs_to_to_status(): void
    {
        $toStatus = TicketStatus::factory()->create();
        $history = TicketHistory::factory()->for($toStatus, 'toStatus')->create();

        $this->assertInstanceOf(TicketStatus::class, $history->toStatus);
        $this->assertEquals($toStatus->id, $history->toStatus->id);
    }

    public function test_ticket_history_can_be_created_with_factory(): void
    {
        $history = TicketHistory::factory()->create([
            'note' => 'Status changed after review',
        ]);

        $this->assertDatabaseHas('ticket_histories', [
            'id' => $history->id,
            'note' => 'Status changed after review',
        ]);
    }

    public function test_ticket_history_can_be_updated(): void
    {
        $history = TicketHistory::factory()->create(['note' => 'Old note']);

        $history->update(['note' => 'Updated note']);

        $this->assertDatabaseHas('ticket_histories', [
            'id' => $history->id,
            'note' => 'Updated note',
        ]);
    }

    public function test_ticket_history_can_be_deleted(): void
    {
        $history = TicketHistory::factory()->create();

        $history->delete();

        $this->assertDatabaseMissing('ticket_histories', ['id' => $history->id]);
    }

    public function test_ticket_history_note_can_be_null(): void
    {
        $history = TicketHistory::factory()->create(['note' => null]);

        $this->assertNull($history->note);
    }
}
