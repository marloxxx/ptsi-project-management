<?php

namespace Tests\Feature\Services;

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
