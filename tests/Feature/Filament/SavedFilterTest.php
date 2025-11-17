<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Services\SavedFilterServiceInterface;
use App\Models\Project;
use App\Models\SavedFilter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SavedFilterTest extends TestCase
{
    use RefreshDatabase;

    private SavedFilterServiceInterface $savedFilterService;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->savedFilterService = $this->app->make(SavedFilterServiceInterface::class);

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();

        // Add user to project
        $this->project->members()->attach($this->user->id);

        Auth::login($this->user);
    }

    public function test_user_can_create_saved_filter(): void
    {
        $data = [
            'name' => 'My Custom Filter',
            'query' => ['status_id' => 1, 'priority_id' => 2],
            'visibility' => 'private',
            'project_id' => $this->project->id,
        ];

        $savedFilter = $this->savedFilterService->create($data);

        $this->assertInstanceOf(SavedFilter::class, $savedFilter);
        $this->assertEquals('My Custom Filter', $savedFilter->name);
        $this->assertEquals('private', $savedFilter->visibility);
        $this->assertEquals($this->user->id, $savedFilter->owner_id);
        $this->assertEquals(\App\Models\User::class, $savedFilter->owner_type);
    }

    public function test_user_can_update_saved_filter(): void
    {
        $savedFilter = SavedFilter::factory()->create([
            'owner_type' => \App\Models\User::class,
            'owner_id' => $this->user->id,
            'name' => 'Original Name',
            'visibility' => 'private',
        ]);

        $updated = $this->savedFilterService->update($savedFilter->id, [
            'name' => 'Updated Name',
            'visibility' => 'public',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('public', $updated->visibility);
    }

    public function test_user_can_delete_saved_filter(): void
    {
        $savedFilter = SavedFilter::factory()->create([
            'owner_type' => \App\Models\User::class,
            'owner_id' => $this->user->id,
        ]);

        $result = $this->savedFilterService->delete($savedFilter->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('saved_filters', ['id' => $savedFilter->id]);
    }

    public function test_user_can_list_accessible_saved_filters(): void
    {
        // Create private filter for user
        SavedFilter::factory()->create([
            'owner_type' => \App\Models\User::class,
            'owner_id' => $this->user->id,
            'visibility' => 'private',
        ]);

        // Create public filter
        SavedFilter::factory()->create([
            'owner_type' => \App\Models\User::class,
            'owner_id' => User::factory()->create()->id,
            'visibility' => 'public',
        ]);

        // Create private filter for another user (should not be accessible)
        SavedFilter::factory()->create([
            'owner_type' => \App\Models\User::class,
            'owner_id' => User::factory()->create()->id,
            'visibility' => 'private',
        ]);

        $filters = $this->savedFilterService->list();

        $this->assertCount(2, $filters);
    }

    public function test_user_can_get_filter_query(): void
    {
        $query = ['status_id' => 1, 'priority_id' => 2];
        $savedFilter = SavedFilter::factory()->create([
            'owner_type' => \App\Models\User::class,
            'owner_id' => $this->user->id,
            'query' => $query,
        ]);

        $retrievedQuery = $this->savedFilterService->getFilterQuery($savedFilter->id);

        $this->assertEquals($query, $retrievedQuery);
    }
}
