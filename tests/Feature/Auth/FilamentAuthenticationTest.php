<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        /** @var RbacSeeder $seeder */
        $seeder = $this->app->make(RbacSeeder::class);
        $seeder->run();
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('filament.admin.pages.dashboard'));

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->get(route('filament.admin.pages.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
    }

    public function test_profile_page_displays_two_factor_controls(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->get(route('filament.admin.pages.my-profile'));

        $response->assertOk();
        $response->assertSeeLivewire('two_factor_authentication');
    }
}
