@if (($app['state'] ?? '') === 'installed')
    <span class="shrink-0 rounded-full bg-success-soft px-1.5 py-0.5 text-2xs font-medium text-success-strong">{{ __('Installed') }}</span>
@elseif (($app['state'] ?? '') === 'to_upgrade')
    <span class="shrink-0 rounded-full bg-warning-soft px-1.5 py-0.5 text-2xs font-medium text-warning-strong">{{ __('Upgrade') }}</span>
@elseif (($app['state'] ?? '') === 'needs_sync')
    <span class="shrink-0 rounded-full bg-warning-soft px-1.5 py-0.5 text-2xs font-medium text-warning-strong">{{ __('Sync pending') }}</span>
@else
    <span class="shrink-0 rounded-full bg-neutral-tertiary px-1.5 py-0.5 text-2xs font-medium text-body-subtle">{{ __('Not installed') }}</span>
@endif
