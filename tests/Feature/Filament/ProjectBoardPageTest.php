<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\ProjectBoard;
use App\Filament\Pages\ProjectTimeline;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBoardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_view_project_board(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $project = Project::factory()->create([
            'name' => 'Project Alpha',
            'ticket_prefix' => 'ALP',
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeek(),
        ]);

        $project->members()->attach($user);

        $priority = TicketPriority::factory()->create(['name' => 'High', 'color' => '#EF4444']);

        $todoStatus = TicketStatus::factory()->for($project)->create([
            'name' => 'Todo',
            'sort_order' => 0,
            'color' => '#2563EB',
        ]);

        TicketStatus::factory()->for($project)->create([
            'name' => 'In Progress',
            'sort_order' => 1,
            'color' => '#F59E0B',
        ]);

        Ticket::factory()->for($project)->for($todoStatus, 'status')->create([
            'priority_id' => $priority->id,
            'name' => 'Implement authentication flow',
            'created_by' => $user->id,
        ]);

        $response = $this->get(ProjectBoard::getUrl(['project' => $project->getKey()]));

        $response
            ->assertOk()
            ->assertSeeText(ProjectBoard::getNavigationLabel() ?? 'Project Board')
            ->assertSeeText('Project Alpha')
            ->assertSeeText('Todo')
            ->assertSeeText('Implement authentication flow');
    }

    public function test_admin_can_view_project_timeline(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $project = Project::factory()->create([
            'name' => 'Project Beta',
            'ticket_prefix' => 'BET',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $project->members()->attach($user);

        $response = $this->get(ProjectTimeline::getUrl());

        $response
            ->assertOk()
            ->assertSeeText(ProjectTimeline::getNavigationLabel() ?? 'Project Timeline')
            ->assertSeeText('Project Beta');
    }
}
