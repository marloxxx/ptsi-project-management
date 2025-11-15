<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'ticket_status_id' => TicketStatus::factory(),
            'priority_id' => TicketPriority::factory(),
            'epic_id' => null,
            'created_by' => User::factory(),
            'uuid' => strtoupper(Str::random(8)),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(2, true),
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 weeks'),
        ];
    }
}
