<div class="mb-8 flex flex-col items-center text-center">
    <div
        class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-fg-brand text-white"
        aria-hidden="true"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3.75 7.5L12 3l8.25 4.5M3.75 7.5v9L12 21l8.25-4.5v-9M3.75 7.5L12 12m0 0l8.25-4.5M12 12v9" />
        </svg>
    </div>
    <h1 class="text-lg font-semibold text-heading">
        {{ __('Sign in to :app', ['app' => (string) (($velmShell ?? [])['app_name'] ?? config('app.name', 'Velm'))]) }}
    </h1>
    <p class="mt-1 text-xs text-body-subtle">{{ __('Use your Velm account credentials.') }}</p>
</div>
