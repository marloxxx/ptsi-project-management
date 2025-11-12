<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Application\Policies\RolePolicy;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;

    private RolePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RbacSeeder $seeder */
        $seeder = $this->app->make(RbacSeeder::class);
        $seeder->run();

        $this->policy = $this->app->make(RolePolicy::class);
    }

    public function test_admin_can_manage_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('manager');

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $role));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $role));
        $this->assertTrue($this->policy->delete($admin, $role));
    }

    public function test_staff_cannot_manage_roles(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $role = Role::findByName('manager');

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertFalse($this->policy->view($staff, $role));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $role));
        $this->assertFalse($this->policy->delete($staff, $role));
    }
}
