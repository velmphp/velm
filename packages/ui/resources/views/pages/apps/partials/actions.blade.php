@php
    $compact = $compact ?? false;
    $detail = $detail ?? false;
    $btn = $compact ? 'px-2.5 py-1 text-2xs' : 'px-3 py-1.5 text-xs';
    $name = $app['name'];
    $state = $app['state'] ?? '';
    $depsMissing = $app['deps_missing'] ?? [];
    $depsUnknown = $app['deps_unknown'] ?? [];
    $installClick = $detail ? 'installModule' : "installModule('{$name}')";
    $upgradeClick = $detail ? 'upgradeModule' : "upgradeModule('{$name}')";
    $syncClick = $detail ? 'syncModule' : "syncModule('{$name}')";
@endphp

<div class="flex flex-wrap items-center justify-end {{ $compact ? 'gap-1.5' : 'gap-2' }}">
    @if ($state === 'uninstalled')
        @if ($depsUnknown !== [])
            <button
                type="button"
                disabled
                title="{{ __('Missing deps not on disk:') }} {{ implode(', ', $depsUnknown) }}"
                class="{{ $btn }} cursor-not-allowed rounded-md bg-neutral-tertiary font-medium text-body-subtle"
            >
                {{ __('Missing deps') }}
            </button>
        @else
            <button
                type="button"
                wire:click="{{ $installClick }}"
                wire:confirm="{{ $depsMissing !== [] ? __('Install :name? This will also install: :deps', ['name' => $name, 'deps' => implode(', ', $depsMissing)]) : __('Install :name?', ['name' => $name]) }}"
                class="{{ $btn }} rounded-md bg-fg-brand font-semibold text-white transition-opacity hover:opacity-90"
            >
                {{ __('Install') }}
                @if ($depsMissing !== [] && $compact)
                    (+{{ count($depsMissing) }})
                @elseif ($depsMissing !== [])
                    (+ {{ count($depsMissing) }} {{ count($depsMissing) === 1 ? __('dep') : __('deps') }})
                @endif
            </button>
        @endif
    @elseif ($state === 'to_upgrade')
        <button
            type="button"
            wire:click="{{ $upgradeClick }}"
            wire:confirm="{{ __('Upgrade :name from :from to :to?', ['name' => $name, 'from' => $app['installed_version'] ?? '', 'to' => $app['available_version'] ?? '']) }}"
            class="{{ $btn }} rounded-md border border-warning bg-warning-soft font-semibold text-warning-strong transition-opacity hover:opacity-90"
        >
            @if (! $compact)
                {{ __('Upgrade to') }} {{ $app['available_version'] ?? '' }}
            @else
                {{ __('Upgrade') }}
            @endif
        </button>
        <button
            type="button"
            wire:click="{{ $syncClick }}"
            wire:confirm="{{ __('Sync :name? Re-applies schema and reloads views/menus.', ['name' => $name]) }}"
            class="{{ $btn }} rounded-md border border-default bg-neutral-primary font-medium text-body transition-colors hover:bg-neutral-secondary"
        >
            {{ __('Sync') }}
        </button>
    @elseif ($state === 'needs_sync')
        <button
            type="button"
            wire:click="{{ $syncClick }}"
            wire:confirm="{{ __('Sync :name? Applies pending schema changes and reloads views/menus.', ['name' => $name]) }}"
            class="{{ $btn }} rounded-md bg-warning-strong font-semibold text-white transition-opacity hover:opacity-90"
        >
            {{ __('Sync') }}
        </button>
    @else
        <button
            type="button"
            wire:click="{{ $syncClick }}"
            wire:confirm="{{ __('Sync :name? Re-applies schema diff and reloads views/menus from disk.', ['name' => $name]) }}"
            class="{{ $btn }} rounded-md border border-default bg-neutral-primary font-medium text-body transition-colors hover:bg-neutral-secondary"
        >
            {{ __('Sync') }}
        </button>
    @endif

    @if ($compact)
        <a
            href="{{ \Velm\Admin\Pages\AppsDetailPage::getUrl(['name' => $name]) }}"
            wire:navigate
            class="{{ $btn }} rounded-md border border-default font-medium text-body transition-colors hover:bg-neutral-secondary"
        >
            {{ __('Details') }}
        </a>
    @endif
</div>
