<?php

namespace Tests\Feature\Livewire;

use App\Livewire\External\Login;
use App\Models\ExternalAccessToken;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalPortalLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        ]);
    }

    public function test_user_can_authenticate_with_valid_password(): void
    {
        $project = Project::factory()->create();
        $token = ExternalAccessToken::factory()
            ->for($project)
            ->create([
                'password' => Hash::make('secret-pass'),
            ]);

        Livewire::test(Login::class, ['token' => $token->access_token])
            ->set('password', 'secret-pass')
            ->call('login')
            ->assertRedirect(route('external.dashboard', ['token' => $token->access_token]));

        $this->assertTrue(session()->has("external_portal_authenticated_{$token->access_token}"));
        $this->assertSame(
            $token->project_id,
            session()->get("external_portal_project_{$token->access_token}")
        );
    }

    public function test_invalid_password_returns_validation_error(): void
    {
        $project = Project::factory()->create();
        $token = ExternalAccessToken::factory()
            ->for($project)
            ->create([
                'password' => Hash::make('secret-pass'),
            ]);

        Livewire::test(Login::class, ['token' => $token->access_token])
            ->set('password', 'wrong-pass')
            ->call('login')
            ->assertHasErrors(['password']);

        $this->assertFalse(session()->has("external_portal_authenticated_{$token->access_token}"));
    }
}
