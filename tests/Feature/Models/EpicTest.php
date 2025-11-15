<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Epic;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpicTest extends TestCase
{
    use RefreshDatabase;

    public function test_epic_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $epic = Epic::factory()->for($project)->create();

        $this->assertInstanceOf(Project::class, $epic->project);
        $this->assertEquals($project->id, $epic->project->id);
    }

    public function test_epic_has_many_tickets(): void
    {
        $epic = Epic::factory()->create();
        $tickets = Ticket::factory()->count(3)->for($epic, 'epic')->create();

        $this->assertCount(3, $epic->tickets);
        $this->assertTrue($epic->tickets->contains($tickets->first()));
    }

    public function test_epic_casts_dates_correctly(): void
    {
        $epic = Epic::factory()->create([
            'start_date' => '2024-01-15',
            'end_date' => '2024-12-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $epic->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $epic->end_date);
        $this->assertEquals('2024-01-15', $epic->start_date->format('Y-m-d'));
        $this->assertEquals('2024-12-31', $epic->end_date->format('Y-m-d'));
    }

    public function test_epic_can_be_created_with_factory(): void
    {
        $epic = Epic::factory()->create([
            'name' => 'User Authentication Epic',
            'description' => 'Implement user authentication system',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('epics', [
            'id' => $epic->id,
            'name' => 'User Authentication Epic',
            'sort_order' => 1,
        ]);
    }

    public function test_epic_can_be_updated(): void
    {
        $epic = Epic::factory()->create(['name' => 'Old Name']);

        $epic->update(['name' => 'New Name']);

        $this->assertDatabaseHas('epics', [
            'id' => $epic->id,
            'name' => 'New Name',
        ]);
    }

    public function test_epic_can_be_deleted(): void
    {
        $epic = Epic::factory()->create();

        $epic->delete();

        $this->assertDatabaseMissing('epics', ['id' => $epic->id]);
    }
}
