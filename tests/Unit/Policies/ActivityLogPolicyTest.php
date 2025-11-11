<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Application\Policies\ActivityLogPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_with_permission_can_view_activity_logs(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('audit-logs.view', 'web');
        $user->givePermissionTo('audit-logs.view');

        $policy = app(ActivityLogPolicy::class);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, new Activity));
    }

    public function test_user_without_permission_cannot_view_activity_logs(): void
    {
        $user = User::factory()->create();

        $policy = app(ActivityLogPolicy::class);

        $this->assertFalse($policy->viewAny($user));
    }
}
