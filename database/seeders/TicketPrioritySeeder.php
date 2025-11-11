<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            [
                'name' => 'Low',
                'color' => '#10B981',
                'sort_order' => 0,
            ],
            [
                'name' => 'Medium',
                'color' => '#F59E0B',
                'sort_order' => 1,
            ],
            [
                'name' => 'High',
                'color' => '#EF4444',
                'sort_order' => 2,
            ],
            [
                'name' => 'Critical',
                'color' => '#7C3AED',
                'sort_order' => 3,
            ],
        ];

        foreach ($priorities as $priority) {
            TicketPriority::updateOrCreate(
                ['name' => $priority['name']],
                ['color' => $priority['color'], 'sort_order' => $priority['sort_order']]
            );
        }
    }
}
