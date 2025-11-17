<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\CustomFieldsRelationManager;
use App\Models\Project;
use App\Models\ProjectCustomField;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomFieldManagementTest extends TestCase
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

        // Clear permission cache to ensure fresh permissions
        $user->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_create_text_custom_field_via_relation_manager(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(CustomFieldsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make(CreateAction::class)->table())
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'key' => 'client_name',
                    'label' => 'Client Name',
                    'type' => 'text',
                    'required' => true,
                    'active' => true,
                    'order' => 1,
                ],
            )
            ->assertNotified();

        $this->assertDatabaseHas('project_custom_fields', [
            'project_id' => $project->getKey(),
            'key' => 'client_name',
            'label' => 'Client Name',
            'type' => 'text',
            'required' => true,
            'active' => true,
            'order' => 1,
        ]);
    }

    public function test_admin_can_create_select_custom_field_with_options(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(CustomFieldsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'key' => 'priority_level',
                    'label' => 'Priority Level',
                    'type' => 'select',
                    'options' => "Low\nMedium\nHigh\nCritical",
                    'required' => false,
                    'active' => true,
                    'order' => 2,
                ],
            )
            ->assertNotified();

        $field = ProjectCustomField::where('key', 'priority_level')->first();

        $this->assertNotNull($field);
        $this->assertIsArray($field->options);
        $this->assertCount(4, $field->options);
        $this->assertContains('Low', $field->options);
        $this->assertContains('Critical', $field->options);
    }

    public function test_admin_can_update_custom_field(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        $admin->refresh();
        $admin->load('roles.permissions');

        $field = ProjectCustomField::factory()
            ->for($project)
            ->create([
                'key' => 'old_key',
                'label' => 'Old Label',
                'type' => 'text',
            ]);

        /** @var ProjectCustomField $field */
        $field = ProjectCustomField::with('project.members')->findOrFail($field->getKey());

        Livewire::test(CustomFieldsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('edit')->table($field))
            ->callAction(
                TestAction::make('edit')->table($field),
                data: [
                    'key' => 'new_key',
                    'label' => 'New Label',
                    'type' => 'number',
                    'required' => true,
                ],
            )
            ->assertNotified();

        $field->refresh();

        $this->assertSame('new_key', $field->key);
        $this->assertSame('New Label', $field->label);
        $this->assertSame('number', $field->type);
        $this->assertTrue($field->required);
    }

    public function test_admin_can_delete_custom_field(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        $admin->refresh();
        $admin->load('roles.permissions');

        $field = ProjectCustomField::factory()
            ->for($project)
            ->create();

        /** @var ProjectCustomField $field */
        $field = ProjectCustomField::with('project.members')->findOrFail($field->getKey());

        Livewire::test(CustomFieldsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionExists(TestAction::make('delete')->table($field))
            ->callAction(
                TestAction::make('delete')->table($field),
            )
            ->assertNotified();

        $this->assertDatabaseMissing('project_custom_fields', [
            'id' => $field->getKey(),
        ]);
    }

    public function test_non_project_member_cannot_create_custom_field(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        Livewire::test(CustomFieldsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertActionHidden(TestAction::make(CreateAction::class)->table());
    }

    public function test_custom_field_key_must_be_unique_per_project(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        /** @var Project $project */
        $project = Project::with('members')->findOrFail($project->getKey());

        $admin->refresh();
        $admin->load('roles.permissions');

        ProjectCustomField::factory()
            ->for($project)
            ->create([
                'key' => 'existing_key',
            ]);

        Livewire::test(CustomFieldsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'key' => 'existing_key',
                    'label' => 'Duplicate Key',
                    'type' => 'text',
                ],
            )
            ->assertHasActionErrors(['key']);
    }
}
