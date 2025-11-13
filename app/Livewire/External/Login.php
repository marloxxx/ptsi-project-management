<?php

namespace App\Livewire\External;

use App\Domain\Services\ExternalPortalServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.external')]
class Login extends Component
{
    public string $token;

    public string $password = '';

    public ?string $errorMessage = null;

    public ?string $projectName = null;

    private ExternalPortalServiceInterface $portalService;

    public function boot(ExternalPortalServiceInterface $portalService): void
    {
        $this->portalService = $portalService;
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if (Session::get($this->authenticatedCacheKey())) {
            $this->redirectRoute('external.dashboard', ['token' => $this->token]);

            return;
        }

        try {
            $context = $this->portalService->resolveContext($this->token);
        } catch (ModelNotFoundException $exception) {
            abort(404, $exception->getMessage());
        }

        $this->projectName = $context['project']->name ?? null;
    }

    public function login(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ], [], [
            'password' => __('Password'),
        ]);

        $this->errorMessage = null;

        try {
            $context = $this->portalService->resolveContext($this->token);
        } catch (ModelNotFoundException) {
            $this->errorMessage = __('External access is no longer available.');

            return;
        }

        $token = $context['token'];

        if (! $this->portalService->verifyPassword($token, $this->password)) {
            $this->errorMessage = __('The password you entered is invalid.');

            throw ValidationException::withMessages([
                'password' => __('The password you entered is invalid.'),
            ]);
        }

        $this->portalService->markAccessed($token);

        Session::put($this->authenticatedCacheKey(), true);
        Session::put($this->projectCacheKey(), (int) $token->project_id);

        $this->redirectRoute('external.dashboard', ['token' => $this->token]);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.external.login');
    }

    private function authenticatedCacheKey(): string
    {
        return sprintf('external_portal_authenticated_%s', $this->token);
    }

    private function projectCacheKey(): string
    {
        return sprintf('external_portal_project_%s', $this->token);
    }
}
