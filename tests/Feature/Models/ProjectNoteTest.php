<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_note_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $note = ProjectNote::factory()->for($project)->create();

        $this->assertInstanceOf(Project::class, $note->project);
        $this->assertEquals($project->id, $note->project->id);
    }

    public function test_project_note_belongs_to_author(): void
    {
        $user = User::factory()->create();
        $note = ProjectNote::factory()->for($user, 'author')->create();

        $this->assertInstanceOf(User::class, $note->author);
        $this->assertEquals($user->id, $note->author->id);
    }

    public function test_project_note_casts_note_date_correctly(): void
    {
        $note = ProjectNote::factory()->create([
            'note_date' => '2024-06-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $note->note_date);
        $this->assertEquals('2024-06-15', $note->note_date->format('Y-m-d'));
    }

    public function test_project_note_can_be_created_with_factory(): void
    {
        $note = ProjectNote::factory()->create([
            'title' => 'Meeting Notes',
            'body' => 'Discussed project requirements',
        ]);

        $this->assertDatabaseHas('project_notes', [
            'id' => $note->id,
            'title' => 'Meeting Notes',
        ]);
    }

    public function test_project_note_can_be_updated(): void
    {
        $note = ProjectNote::factory()->create(['title' => 'Old Title']);

        $note->update(['title' => 'New Title']);

        $this->assertDatabaseHas('project_notes', [
            'id' => $note->id,
            'title' => 'New Title',
        ]);
    }

    public function test_project_note_can_be_deleted(): void
    {
        $note = ProjectNote::factory()->create();

        $note->delete();

        $this->assertDatabaseMissing('project_notes', ['id' => $note->id]);
    }
}
