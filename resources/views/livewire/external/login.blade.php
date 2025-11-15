<section aria-labelledby="external-login-heading" class="d-flex justify-content-center">
    <article class="card p-5 shadow-lg" style="max-width: 420px; width: 100%;">
        <header class="mb-4 text-center">
            <h1 id="external-login-heading" class="h4 mb-1">
                {{ __('Secure External Access') }}
            </h1>
            <p class="text-secondary mb-0">
                @if ($projectName)
                    {{ __('Access the project dashboard for ":project".', ['project' => $projectName]) }}
                @else
                    {{ __('Enter the access password provided to you.') }}
                @endif
            </p>
        </header>

        <form wire:submit.prevent="login" class="d-grid gap-4">
            <label for="password" class="form-label text-uppercase text-secondary fw-semibold small mb-1">
                {{ __('Access Password') }}
            </label>
            <input id="password" type="password" wire:model.defer="password" autocomplete="current-password" required
                class="form-control" placeholder="{{ __('Enter password') }}" />

            @error('password')
                <p class="text-danger small" role="alert">{{ $message }}</p>
            @enderror

            @if ($errorMessage)
                <p class="text-danger small" role="alert">{{ $errorMessage }}</p>
            @endif

            <button type="submit" class="btn btn-primary">
                {{ __('Enter Dashboard') }}
            </button>
        </form>
    </article>
</section>
