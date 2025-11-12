<?php

namespace Tests\Feature\Services;

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ProjectServiceInterface $service */
        $service = $this->app->make(ProjectServiceInterface::class);
        $this->service = $service;
    }

    public function test_project_service_creates_project_with_members_and_default_statuses(): void
    {

        $owner = User::factory()->create();

        $project = $this->service->create([
            'name' => 'Migration Pilot',
            'description' => 'Initial migration project',
            'ticket_prefix' => 'MIG',
            'color' => '#184980',
            'start_date' => now()->toDateString(),
        ], [$owner->id]);

        $this->assertInstanceOf(Project::class, $project);

        $this->assertDatabaseHas('projects', [
            'name' => 'Migration Pilot',
            'ticket_prefix' => 'MIG',
        ]);

        $this->assertCount(4, $project->ticketStatuses);
        $this->assertContains($owner->id, $project->members->pluck('id')->all());
    }

    public function test_project_service_generates_external_access_token(): void
    {
        $project = $this->service->create([
            'name' => 'Portal Rollout',
            'description' => 'External portal rollout',
            'ticket_prefix' => 'PRT',
        ]);

        $token = $this->service->generateExternalAccess($project->id, 'Client Portal');

        $this->assertNotNull($token->plain_password ?? null);
        $this->assertTrue((bool) $token->is_active);

        $this->assertDatabaseHas('external_access_tokens', [
            'project_id' => $project->id,
            'name' => 'Client Portal',
        ]);
    }

    public function test_add_note_sets_defaults_and_logs_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = $this->service->create([
            'name' => 'Documentation Refresh',
            'ticket_prefix' => 'DOC',
        ]);

        $note = $this->service->addNote($project->id, [
            'title' => 'Kick-off',
            'body' => 'Captured initial scoping decisions.',
        ]);

        $this->assertNotNull($note->note_date);
        $this->assertSame($user->getKey(), $note->created_by);

        $this->assertDatabaseHas('project_notes', [
            'id' => $note->id,
            'project_id' => $project->id,
            'title' => 'Kick-off',
        ]);

        $this->assertTrue(
            Activity::query()
                ->where('event', 'created')
                ->where('subject_type', $note::class)
                ->where('subject_id', $note->id)
                ->exists()
        );
    }

    public function test_add_status_records_activity(): void
    {
        $project = $this->service->create([
            'name' => 'QA Hardening',
            'ticket_prefix' => 'QA',
        ]);

        $status = $this->service->addStatus($project->id, [
            'name' => 'QA Ready',
            'color' => '#2563EB',
        ]);

        $this->assertInstanceOf(TicketStatus::class, $status);

        $this->assertDatabaseHas('ticket_statuses', [
            'id' => $status->id,
            'project_id' => $project->id,
            'name' => 'QA Ready',
        ]);

        $this->assertTrue(
            Activity::query()
                ->where('event', 'created')
                ->where('subject_type', $status::class)
                ->where('subject_id', $status->id)
                ->exists()
        );
    }
}
