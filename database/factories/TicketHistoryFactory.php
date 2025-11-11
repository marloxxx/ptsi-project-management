<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketHistory>
 */
class TicketHistoryFactory extends Factory
{
    protected $model = TicketHistory::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'from_ticket_status_id' => TicketStatus::factory(),
            'to_ticket_status_id' => TicketStatus::factory(),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
