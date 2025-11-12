<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Application\Policies\UserPolicy;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RbacSeeder $seeder */
        $seeder = $this->app->make(RbacSeeder::class);
        $seeder->run();

        $this->policy = $this->app->make(UserPolicy::class);
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $target));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $target));
        $this->assertTrue($this->policy->delete($admin, $target));
    }

    public function test_manager_cannot_delete_users(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $target = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($manager));
        $this->assertTrue($this->policy->view($manager, $target));
        $this->assertFalse($this->policy->delete($manager, $target));
    }

    public function test_user_can_update_self_even_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->update($user, $user));
    }
}
