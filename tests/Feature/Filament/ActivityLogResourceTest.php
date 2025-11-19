<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityLogResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        Filament::setCurrentPanel('admin');
        $this->seed(RbacSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        // Clear permission cache to ensure fresh permissions
        $user->load('roles.permissions');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user);

        return $user;
    }

    public function test_staff_cannot_access_activity_logs(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('filament.admin.resources.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_activity_log_listing(): void
    {
        $admin = $this->actingAsRole('admin');

        // Create some activity logs
        $project = Project::factory()->create();
        activity()
            ->performedOn($project)
            ->causedBy($admin)
            ->log('created');

        $this->get(route('filament.admin.resources.activity-logs.index'))
            ->assertOk()
            ->assertSee('Audit Logs');
    }

    public function test_user_with_permission_can_view_activity_logs(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('audit-logs.view', 'web');
        $user->givePermissionTo('audit-logs.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user);

        // Create some activity logs
        $project = Project::factory()->create();
        activity()
            ->performedOn($project)
            ->causedBy($user)
            ->log('created');

        $this->get(route('filament.admin.resources.activity-logs.index'))
            ->assertOk()
            ->assertSee('Audit Logs');
    }

    public function test_admin_can_view_activity_log_details(): void
    {
        $admin = $this->actingAsRole('admin');

        // Create an activity log
        $project = Project::factory()->create();
        activity()
            ->performedOn($project)
            ->causedBy($admin)
            ->log('created');

        /** @var Activity $activity */
        $activity = Activity::latest()->first();

        Livewire::test(ViewActivityLog::class, ['record' => $activity->getKey()])
            ->assertSuccessful();

        $this->get(route('filament.admin.resources.activity-logs.view', ['record' => $activity->getKey()]))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_view_activity_logs(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('filament.admin.resources.activity-logs.index'))
            ->assertForbidden();
    }
}
