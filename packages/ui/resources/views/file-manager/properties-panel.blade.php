@php
    $att = $att ?? [];
    $panelOnly = ! empty($panelOnly);
    $bodyClass = $panelOnly
        ? 'space-y-3'
        : 'grid gap-6 grid-cols-1 lg:grid-cols-[minmax(0,1fr)_28rem]';
    $dims = $dimensions ?? null;
@endphp

<div class="{{ $bodyClass }}">
    <div @class([
        'rounded-lg border border-default bg-neutral-secondary flex items-center justify-center overflow-hidden',
        'aspect-[4/3]' => $panelOnly,
        'aspect-[4/3] lg:aspect-auto lg:min-h-[24rem]' => ! $panelOnly,
    ])>
        @if ($isImage ?? false)
            <img
                src="/api/attachment/{{ (int) ($att['id'] ?? 0) }}/download"
                alt="{{ $att['name'] ?? '' }}"
                loading="lazy"
                class="w-full h-full object-contain bg-checker"
                onerror="this.style.display='none'"
            >
        @else
            <div class="flex flex-col items-center text-body-subtle gap-2">
                <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <span class="text-xs uppercase tracking-wider">{{ $extension ?: 'file' }}</span>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @if ($panelOnly)
            <h2 class="text-lg font-semibold text-heading truncate" title="{{ $att['name'] ?? '' }}">
                {{ $att['name'] ?? '' }}
            </h2>
        @endif

        @if (! empty($folderChain))
            <nav class="text-xs text-body-subtle flex items-center flex-wrap gap-1">
                <a href="/web/files/library" class="hover:text-fg-brand hover:underline">{{ __('Library') }}</a>
                @foreach ($folderChain as $crumb)
                    <span>›</span>
                    <a href="/web/files/library?folder_id={{ (int) $crumb['id'] }}"
                       class="hover:text-fg-brand hover:underline">{{ $crumb['name'] }}</a>
                @endforeach
            </nav>
        @endif

        <dl class="grid grid-cols-[8rem_minmax(0,1fr)] gap-y-2 text-sm">
            <dt class="text-body-subtle">{{ __('Name') }}</dt>
            <dd class="text-heading font-medium break-words">{{ $att['name'] ?? '—' }}</dd>

            <dt class="text-body-subtle">{{ __('Filename') }}</dt>
            <dd class="text-body break-words">{{ $att['datas_fname'] ?? '—' }}</dd>

            <dt class="text-body-subtle">{{ __('Type') }}</dt>
            <dd class="text-body break-words">
                <code class="text-xs">{{ $mimetype ?: 'application/octet-stream' }}</code>
            </dd>

            <dt class="text-body-subtle">{{ __('Size') }}</dt>
            <dd class="text-body">{{ $fileSizeLabel ?? '—' }}</dd>

            @if (($isImage ?? false) && is_array($dims))
                <dt class="text-body-subtle">{{ __('Dimensions') }}</dt>
                <dd class="text-body">{{ $dims['width'] }} × {{ $dims['height'] }} px</dd>
            @elseif ($isImage ?? false)
                <dt class="text-body-subtle">{{ __('Dimensions') }}</dt>
                <dd class="text-body-subtle">—</dd>
            @endif

            <dt class="text-body-subtle">{{ __('Created') }}</dt>
            <dd class="text-body">{{ $att['created_at'] ?? '—' }}</dd>

            <dt class="text-body-subtle">{{ __('Updated') }}</dt>
            <dd class="text-body">{{ $att['updated_at'] ?? '—' }}</dd>

            @if (! empty($att['res_model']))
                <dt class="text-body-subtle">{{ __('Linked to') }}</dt>
                <dd class="text-body break-words">
                    @if (! empty($ownerUrl))
                        <a href="{{ $ownerUrl }}" class="text-fg-brand hover:underline">
                            {{ $att['res_model'] }} #{{ (int) ($att['res_id'] ?? 0) }}
                        </a>
                    @else
                        {{ $att['res_model'] }}@if (! empty($att['res_id'])) #{{ (int) $att['res_id'] }}@endif
                    @endif
                </dd>
            @endif

            <dt class="text-body-subtle">{{ __('Storage') }}</dt>
            <dd class="text-body">
                @if (($att['type'] ?? '') === 'url')
                    {{ __('External URL') }}
                @elseif (! empty($att['storage_key']))
                    {{ __('Local backend') }}
                @else
                    {{ __('Inline (DB)') }}
                @endif
            </dd>

            <dt class="text-body-subtle">{{ __('Public') }}</dt>
            <dd>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        class="w-4 h-4 rounded border-default bg-neutral-secondary text-fg-brand checked:bg-fg-brand focus:ring-2 focus:ring-fg-brand"
                        @checked((bool) ($att['public'] ?? false))
                        onchange="(async (el) => {
                            const r = await fetch('/web/files/bulk/public', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '',
                                },
                                body: JSON.stringify({ ids: [{{ (int) ($att['id'] ?? 0) }}], public: el.checked }),
                            });
                            if (r.ok) window.location.reload();
                        })(this)"
                    >
                    <span class="text-body">{{ ($att['public'] ?? false) ? __('Public') : __('Private') }}</span>
                </label>
            </dd>
        </dl>

        @if ($panelOnly)
            <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-default">
                <a href="/api/attachment/{{ (int) ($att['id'] ?? 0) }}/download"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs bg-fg-brand text-white hover:opacity-90 transition">
                    {{ __('Download') }}
                </a>
                <a href="/web/files/{{ (int) ($att['id'] ?? 0) }}/properties"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs border border-default text-body hover:bg-neutral-secondary transition">
                    {{ __('Open properties page') }}
                </a>
            </div>
        @endif
    </div>
</div>
