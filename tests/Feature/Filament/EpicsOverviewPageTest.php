<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\EpicsOverview;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpicsOverviewPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_view_epics_overview(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $project = Project::factory()->create([
            'name' => 'Project Gamma',
            'ticket_prefix' => 'GAM',
            'color' => '#2563EB',
        ]);

        $project->members()->attach($user);

        $status = TicketStatus::factory()
            ->for($project)
            ->create([
                'name' => 'In Progress',
                'color' => '#F97316',
            ]);

        $priority = TicketPriority::factory()->create([
            'name' => 'High',
            'color' => '#EF4444',
        ]);

        $epic = Epic::factory()
            ->for($project)
            ->create([
                'name' => 'Mobile Revamp',
                'description' => 'Perbaikan aplikasi mobile.',
            ]);

        Ticket::factory()
            ->for($project)
            ->create([
                'ticket_status_id' => $status->id,
                'priority_id' => $priority->id,
                'epic_id' => $epic->id,
                'name' => 'Implementasi UI baru',
                'created_by' => $user->id,
            ]);

        $response = $this->get(EpicsOverview::getUrl(['project' => $project->getKey()]));

        $response
            ->assertOk()
            ->assertSeeText('Daftar Epics')
            ->assertSeeText('Mobile Revamp')
            ->assertSeeText('Implementasi UI baru');
    }
}
