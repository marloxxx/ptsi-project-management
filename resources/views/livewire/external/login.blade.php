<section aria-labelledby="external-login-heading" class="flex justify-center">
    <article class="w-full max-w-md rounded-xl border border-slate-200 bg-white/90 p-8 shadow-xl backdrop-blur">
        <header class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#184980]/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#184980]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M16.5 10.5V6.75A4.5 4.5 0 0 0 12 2.25v0a4.5 4.5 0 0 0-4.5 4.5V10.5m0 0H6.75A2.25 2.25 0 0 0 4.5 12.75v6A2.25 2.25 0 0 0 6.75 21h10.5A2.25 2.25 0 0 0 19.5 18.75v-6A2.25 2.25 0 0 0 17.25 10.5H16.5m-9 0h9" />
                </svg>
            </div>
            <h1 id="external-login-heading" class="mb-1 text-lg font-semibold tracking-tight text-slate-900">
                {{ __('Secure External Access') }}
            </h1>
            <p class="text-sm text-slate-500">
                @if ($projectName)
                    {{ __('Access the project dashboard for ":project".', ['project' => $projectName]) }}
                @else
                    {{ __('Enter the access password provided to you.') }}
                @endif
            </p>
        </header>

        <form wire:submit.prevent="login" class="grid gap-5">
            <div>
                <label for="password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ __('Access Password') }}
                </label>
                <input id="password" type="password" wire:model.defer="password" autocomplete="current-password"
                    required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 shadow-sm outline-none ring-0 transition focus:border-[#184980] focus:ring-2 focus:ring-[#184980]/20 sm:text-sm"
                    placeholder="{{ __('Enter password') }}" />
                @error('password')
                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
                @if ($errorMessage)
                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $errorMessage }}</p>
                @endif
            </div>

            <button type="submit"
                class="inline-flex h-10 items-center justify-center rounded-lg bg-[#184980] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#143e6b] focus:outline-none focus:ring-2 focus:ring-[#184980]/30">
                {{ __('Enter Dashboard') }}
            </button>
        </form>
    </article>
</section>
