<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Application\Policies\UserPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_with_permission_can_view_users(): void
    {
        $user = User::factory()->create();

        Permission::findOrCreate('users.view', 'web');
        $user->givePermissionTo('users.view');

        $policy = app(UserPolicy::class);

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_user_without_permission_cannot_view_users(): void
    {
        $user = User::factory()->create();

        $policy = app(UserPolicy::class);

        $this->assertFalse($policy->viewAny($user));
    }

    public function test_user_can_update_self_without_explicit_permission(): void
    {
        $user = User::factory()->create();

        $policy = app(UserPolicy::class);

        $this->assertTrue($policy->update($user, $user));
    }

    public function test_user_cannot_delete_self_even_with_permission(): void
    {
        $user = User::factory()->create();

        Permission::findOrCreate('users.delete', 'web');
        $user->givePermissionTo('users.delete');

        $policy = app(UserPolicy::class);

        $this->assertFalse($policy->delete($user, $user));
    }
}
