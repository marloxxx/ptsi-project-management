<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Domain\Services\UserServiceInterface;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserServiceInterface $userService;

    private RbacSeeder $rbacSeeder;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RbacSeeder $rbacSeeder */
        $rbacSeeder = $this->app->make(RbacSeeder::class);
        $this->rbacSeeder = $rbacSeeder;
        $this->rbacSeeder->run();

        /** @var UserServiceInterface $userService */
        $userService = $this->app->make(UserServiceInterface::class);
        $this->userService = $userService;
    }

    public function test_it_creates_user_with_roles(): void
    {
        $payload = [
            'name' => 'Jane Admin',
            'email' => 'jane.admin@example.com',
            'password' => 'Secret123!',
        ];

        $user = $this->userService->create($payload, ['admin', 'manager']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Jane Admin', $user->name);
        $this->assertTrue(Hash::check('Secret123!', $user->getAuthPassword()));
        $this->assertEqualsCanonicalizing(['admin', 'manager'], $user->getRoleNames()->all());
    }

    public function test_it_updates_user_and_syncs_roles(): void
    {
        $user = $this->userService->create([
            'name' => 'John Staff',
            'email' => 'john.staff@example.com',
            'password' => 'Secret123!',
        ], ['staff']);

        $this->assertTrue($user->hasRole('staff'));

        $result = $this->userService->update((int) $user->getKey(), [
            'name' => 'John Manager',
            'password' => 'NewSecret456!',
        ], ['manager']);

        $this->assertTrue($result);

        $user = $user->fresh('roles');

        $this->assertSame('John Manager', $user->name);
        $this->assertTrue(Hash::check('NewSecret456!', $user->getAuthPassword()));
        $this->assertEqualsCanonicalizing(['manager'], $user->getRoleNames()->all());
        $this->assertFalse($user->hasRole('staff'));
    }
}
