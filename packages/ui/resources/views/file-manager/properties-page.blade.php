<div>
    @push('page-actions')
        <a href="/api/attachment/{{ (int) ($att['id'] ?? 0) }}/download"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white rounded-md bg-fg-brand hover:opacity-90 transition">
            {{ __('Download') }}
        </a>
        @if (! empty($ownerUrl))
            <a href="{{ $ownerUrl }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium border border-default text-body rounded-md hover:bg-neutral-secondary transition">
                {{ __('Open linked record') }}
            </a>
        @endif
        <button
            type="button"
            onclick="(async () => {
                const ok = await (window.pvConfirm
                    ? window.pvConfirm(@js(__('Delete this file?')), { title: @js(__('Delete file')), variant: 'danger', confirmLabel: @js(__('Delete')) })
                    : Promise.resolve(confirm(@js(__('Delete this file?')))));
                if (!ok) return;
                const r = await fetch('/api/attachment/{{ (int) ($att['id'] ?? 0) }}', {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                });
                if (r.ok || r.status === 204) window.location = '/web/files/library';
                else window.pvAlert && window.pvAlert(@js(__('Delete failed.')), { variant: 'danger' });
            })()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium border border-fg-danger/30 text-fg-danger rounded-md hover:bg-danger-soft transition"
        >
            {{ __('Delete') }}
        </button>
    @endpush

    @push('before-livewire')
        @include('velm-ui::file-manager.scripts')
    @endpush

    @include('velm-ui::file-manager.properties-panel', ['panelOnly' => false])
</div>
