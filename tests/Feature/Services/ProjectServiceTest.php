<?php

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('services');

test('project service creates project with members and default statuses', function (): void {
    /** @var ProjectServiceInterface $service */
    $service = app(ProjectServiceInterface::class);

    $owner = User::factory()->create();

    $project = $service->create([
        'name' => 'Migration Pilot',
        'description' => 'Initial migration project',
        'ticket_prefix' => 'MIG',
        'color' => '#184980',
        'start_date' => now()->toDateString(),
    ], [$owner->id]);

    expect($project)->toBeInstanceOf(Project::class);

    assertDatabaseHas('projects', [
        'name' => 'Migration Pilot',
        'ticket_prefix' => 'MIG',
    ]);

    expect($project->ticketStatuses)->toHaveCount(4);
    expect($project->members->pluck('id'))->toContain($owner->id);
});

test('project service generates external access token', function (): void {
    /** @var ProjectServiceInterface $service */
    $service = app(ProjectServiceInterface::class);

    $project = $service->create([
        'name' => 'Portal Rollout',
        'description' => 'External portal rollout',
        'ticket_prefix' => 'PRT',
    ]);

    $token = $service->generateExternalAccess($project->id, 'Client Portal');

    expect($token->plain_password ?? null)->not->toBeNull();
    expect($token->is_active)->toBeTrue();

    assertDatabaseHas('external_access_tokens', [
        'project_id' => $project->id,
        'name' => 'Client Portal',
    ]);
});
