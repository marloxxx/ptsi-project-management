<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsServiceInterface $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        Carbon::setTestNow('2025-01-15 12:00:00');

        $this->analyticsService = app(AnalyticsServiceInterface::class);
    }

    public function test_overview_stats_for_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('staff');

        $projects = Project::factory()->count(2)->create();
        $status = TicketStatus::factory()->for($projects->first())->create([
            'is_completed' => false,
        ]);

        $tickets = Ticket::factory()
            ->count(3)
            ->for($projects->first())
            ->create([
                'ticket_status_id' => $status->getKey(),
                'created_by' => $superAdmin->getKey(),
                'start_date' => Carbon::now()->subWeek(),
                'due_date' => Carbon::now()->addWeek(),
            ]);

        $projects->first()->members()->attach([$superAdmin->getKey(), $otherUser->getKey()]);

        foreach ($tickets as $ticket) {
            $ticket->assignees()->attach($superAdmin->getKey());
        }

        $stats = $this->mapStatsByLabel($this->analyticsService->getOverviewStats($superAdmin));

        $this->assertSame(2, $stats['Total Projects']['value']);
        $this->assertSame(3, $stats['Total Tickets']['value']);
        $this->assertSame(3, $stats['My Assigned Tickets']['value']);
        $this->assertSame(2, $stats['Team Members']['value']);
    }

    public function test_member_scoped_datasets(): void
    {
        $member = User::factory()->create();
        $member->assignRole('manager');

        $teammate = User::factory()->create();
        $teammate->assignRole('staff');

        $accessibleProject = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $openStatus = TicketStatus::factory()->for($accessibleProject)->create([
            'is_completed' => false,
        ]);
        $doneStatus = TicketStatus::factory()->for($accessibleProject)->create([
            'is_completed' => true,
        ]);

        $overdueTicket = Ticket::factory()->for($accessibleProject)->create([
            'ticket_status_id' => $openStatus->getKey(),
            'created_by' => $member->getKey(),
            'start_date' => Carbon::now()->subWeeks(2),
            'due_date' => Carbon::now()->subDay(),
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $completedTicket = Ticket::factory()->for($accessibleProject)->create([
            'ticket_status_id' => $doneStatus->getKey(),
            'created_by' => $member->getKey(),
            'start_date' => Carbon::now()->subWeeks(2),
            'due_date' => Carbon::now()->addDays(5),
            'updated_at' => Carbon::now()->subDays(1),
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $newTicket = Ticket::factory()->for($accessibleProject)->create([
            'ticket_status_id' => $openStatus->getKey(),
            'created_by' => $member->getKey(),
            'start_date' => Carbon::now()->subDays(2),
            'due_date' => Carbon::now()->addDays(10),
            'created_at' => Carbon::now()->subDays(2),
        ]);

        foreach ([$overdueTicket, $completedTicket, $newTicket] as $ticket) {
            $ticket->assignees()->attach($member->getKey());
        }

        $accessibleProject->members()->attach([$member->getKey(), $teammate->getKey()]);

        // Tickets in an inaccessible project should be ignored.
        Ticket::factory()->for($otherProject)->create([
            'ticket_status_id' => TicketStatus::factory()->for($otherProject)->create()->getKey(),
            'created_by' => $teammate->getKey(),
        ]);

        $stats = $this->mapStatsByLabel($this->analyticsService->getOverviewStats($member));

        $this->assertSame(1, $stats['My Projects']['value']);
        $this->assertSame(3, $stats['Project Tickets']['value']);
        $this->assertSame(3, $stats['My Assigned Tickets']['value']);
        $this->assertSame(3, $stats['My Created Tickets']['value']);
        $this->assertSame(1, $stats['My Overdue Tasks']['value']);
        $this->assertSame(1, $stats['Completed This Week']['value']);
        $this->assertSame(2, $stats['New Tasks This Week']['value']);
        $this->assertSame(1, $stats['Team Members']['value']); // teammate only

        $ticketsPerProject = $this->analyticsService->getTicketsPerProject($member);
        $this->assertSame([$accessibleProject->name], $ticketsPerProject['labels']);
        $this->assertSame([3], $ticketsPerProject['data']);

        // Monthly trend
        Ticket::query()->whereKey($overdueTicket->getKey())->update([
            'created_at' => Carbon::create(2024, 11, 10),
        ]);

        $trend = $this->analyticsService->getMonthlyTicketTrend($member);
        $this->assertNotEmpty($trend['labels']);
        $this->assertSame('Nov 2024', $trend['labels'][0]);

        // User statistics constrained to current user.
        $userStats = $this->analyticsService->getUserStatistics($member);
        $this->assertSame([$member->name], $userStats['labels']);
        $this->assertSame([1], $userStats['projects']);
        $this->assertSame([3], $userStats['assignments']);
    }

    public function test_recent_activity_query_limits_by_project_membership(): void
    {
        $member = User::factory()->create();
        $member->assignRole('manager');

        $accessibleProject = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $status = TicketStatus::factory()->for($accessibleProject)->create();
        $otherStatus = TicketStatus::factory()->for($otherProject)->create();

        $accessibleTicket = Ticket::factory()->for($accessibleProject)->create([
            'ticket_status_id' => $status->getKey(),
            'created_by' => $member->getKey(),
        ]);

        $accessibleTicket->assignees()->attach($member->getKey());
        $accessibleProject->members()->attach($member->getKey());

        $hiddenTicket = Ticket::factory()->for($otherProject)->create([
            'ticket_status_id' => $otherStatus->getKey(),
        ]);

        TicketHistory::factory()->for($accessibleTicket)->create([
            'user_id' => $member->getKey(),
            'from_ticket_status_id' => $status->getKey(),
            'to_ticket_status_id' => $status->getKey(),
        ]);

        TicketHistory::factory()->for($hiddenTicket)->create([
            'user_id' => User::factory()->create()->getKey(),
            'from_ticket_status_id' => $otherStatus->getKey(),
            'to_ticket_status_id' => $otherStatus->getKey(),
        ]);

        $results = $this->analyticsService
            ->recentActivityQuery($member)
            ->pluck('ticket_id')
            ->all();

        $this->assertSame([$accessibleTicket->getKey()], $results);
    }

    public function test_get_cumulative_flow_diagram(): void
    {
        $project = Project::factory()->create();
        $status1 = TicketStatus::factory()->for($project)->create(['sort_order' => 1]);
        $status2 = TicketStatus::factory()->for($project)->create(['sort_order' => 2, 'is_completed' => true]);

        Ticket::factory()->for($project)->create([
            'ticket_status_id' => $status1->getKey(),
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $result = $this->analyticsService->getCumulativeFlowDiagram($project->id, 30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);
    }

    public function test_get_lead_cycle_time(): void
    {
        $project = Project::factory()->create();
        $completedStatus = TicketStatus::factory()->for($project)->create(['is_completed' => true]);

        $ticket = Ticket::factory()->for($project)->create([
            'ticket_status_id' => $completedStatus->getKey(),
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(5),
        ]);

        $result = $this->analyticsService->getLeadCycleTime($project->id, 30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('lead_time', $result);
        $this->assertArrayHasKey('cycle_time', $result);
        $this->assertArrayHasKey('chart_data', $result);
    }

    public function test_get_throughput(): void
    {
        $project = Project::factory()->create();
        $completedStatus = TicketStatus::factory()->for($project)->create(['is_completed' => true]);

        Ticket::factory()->for($project)->create([
            'ticket_status_id' => $completedStatus->getKey(),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        $result = $this->analyticsService->getThroughput($project->id, 30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('avg_per_day', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function test_get_project_burndown(): void
    {
        $project = Project::factory()->create();
        $completedStatus = TicketStatus::factory()->for($project)->create(['is_completed' => true]);

        Ticket::factory()->for($project)->count(5)->create([
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $result = $this->analyticsService->getProjectBurndown($project->id, 30);

        $this->assertIsArray($result);
        if (! empty($result)) {
            $this->assertArrayHasKey('date', $result[0]);
            $this->assertArrayHasKey('remaining', $result[0]);
            $this->assertArrayHasKey('ideal', $result[0]);
        }
    }

    public function test_get_project_velocity(): void
    {
        $project = Project::factory()->create();
        $completedStatus = TicketStatus::factory()->for($project)->create(['is_completed' => true]);

        Ticket::factory()->for($project)->count(3)->create([
            'ticket_status_id' => $completedStatus->getKey(),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        $result = $this->analyticsService->getProjectVelocity($project->id, 8);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('avg_velocity', $result);
    }

    /**
     * @param  array<int, array{label: string, value: int, description?: string, icon?: string, color?: string}>  $stats
     * @return Collection<string, array{label: string, value: int, description?: string, icon?: string, color?: string}>
     */
    private function mapStatsByLabel(array $stats): Collection
    {
        return collect($stats)->keyBy(static fn (array $stat): string => $stat['label']);
    }
}
