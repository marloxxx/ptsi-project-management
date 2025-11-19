<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\ExternalAccessToken;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalAccessActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        Filament::setCurrentPanel('admin');
        $this->seed(RbacSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_generate_rotate_and_deactivate_external_access(): void
    {
        $this->actingAsAdmin();

        /** @var Project $project */
        $project = Project::factory()->create();

        // Generate
        Livewire::test(ViewProject::class, ['record' => $project->getKey()])
            ->callAction('generateExternalAccess')
            ->assertSuccessful();

        $created = ExternalAccessToken::where('project_id', $project->getKey())->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->is_active);
        $this->assertNotEmpty($created->access_token);
        $this->assertNotEmpty($created->password);

        $oldTokenId = $created->getKey();

        // Rotate
        Livewire::test(ViewProject::class, ['record' => $project->getKey()])
            ->callAction('rotateExternalAccess')
            ->assertSuccessful();

        $rotated = ExternalAccessToken::where('project_id', $project->getKey())->first();
        $this->assertNotNull($rotated);
        $this->assertTrue((bool) $rotated->is_active);
        $this->assertNotEquals($oldTokenId, $rotated->getKey());

        // Deactivate
        Livewire::test(ViewProject::class, ['record' => $project->getKey()])
            ->callAction('deactivateExternalAccess')
            ->assertSuccessful();

        $deactivated = ExternalAccessToken::where('project_id', $project->getKey())->first();
        $this->assertNotNull($deactivated);
        $this->assertFalse((bool) $deactivated->is_active);
    }
}
