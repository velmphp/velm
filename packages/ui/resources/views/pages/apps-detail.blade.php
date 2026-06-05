@php
    $app = $this->moduleEntry();
@endphp

<div
    class="space-y-4"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    @if ($app === null)
        <p class="text-sm text-body-subtle">{{ __('Module not found.') }}</p>
    @else
        <div>
            <a href="{{ \Velm\Admin\Pages\AppsPage::getUrl() }}" wire:navigate class="text-sm text-fg-brand hover:underline">
                ← {{ __('Apps') }}
            </a>
        </div>

        <div class="w-full max-w-none overflow-hidden rounded-xl border border-default bg-neutral-primary shadow-sm">
            <div class="flex items-start gap-3 border-b border-default px-5 py-4 sm:px-6">
                @include('velm-ui::pages.apps.partials.icon', ['app' => $app, 'size' => 'w-11 h-11', 'iconSize' => 'w-6 h-6'])
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @include('velm-ui::pages.apps.partials.state-badge', ['app' => $app])
                        <span class="text-xs text-body-subtle">{{ $app['category'] }}</span>
                    </div>
                    @if (($app['author'] ?? '') !== '')
                        <p class="mt-1 text-xs text-body-subtle">{{ __('by') }} {{ $app['author'] }}</p>
                    @endif
                </div>
                <div class="shrink-0">
                    @include('velm-ui::pages.apps.partials.actions', ['app' => $app, 'compact' => false, 'detail' => true])
                </div>
            </div>

            <div class="space-y-6 px-5 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-2xs font-medium tracking-wide text-body-subtle uppercase">{{ __('Technical name') }}</dt>
                        <dd class="mt-0.5 font-mono text-sm text-body">{{ $app['name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xs font-medium tracking-wide text-body-subtle uppercase">{{ __('Version') }}</dt>
                        <dd class="mt-0.5 text-body">
                            @if (($app['state'] ?? '') === 'to_upgrade')
                                <span class="text-warning-strong">{{ $app['installed_version'] }} → {{ $app['available_version'] }}</span>
                            @elseif (($app['state'] ?? '') === 'needs_sync')
                                {{ $app['installed_version'] }}
                                <span class="text-warning-strong">({{ __('sync pending') }})</span>
                            @elseif ($app['installed_version'] ?? null)
                                {{ $app['installed_version'] }}
                                <span class="text-body-subtle">({{ __('available') }} {{ $app['available_version'] }})</span>
                            @else
                                {{ $app['available_version'] }}
                            @endif
                        </dd>
                    </div>
                    @if (($app['depends'] ?? []) !== [])
                        <div class="sm:col-span-2 lg:col-span-2">
                            <dt class="text-2xs font-medium tracking-wide text-body-subtle uppercase">{{ __('Depends on') }}</dt>
                            <dd class="mt-0.5 text-body">
                                @foreach ($app['depends'] as $dep)
                                    <span @class(['text-fg-danger font-medium' => in_array($dep, $app['deps_missing'] ?? [], true)])>{{ $dep }}</span>@if (! $loop->last), @endif
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>

                @if (($app['summary'] ?? '') !== '')
                    <section>
                        <h3 class="mb-2 text-xs font-semibold text-heading">{{ __('Summary') }}</h3>
                        <p class="text-sm leading-relaxed text-body">{{ $app['summary'] }}</p>
                    </section>
                @endif

                @if (($app['schema_diff_summary'] ?? '') !== '')
                    <section>
                        <h3 class="mb-2 text-xs font-semibold text-heading">{{ __('Pending schema') }}</h3>
                        <p class="text-sm text-body">{{ $app['schema_diff_summary'] }}</p>
                    </section>
                @endif

                @if (($app['schema_drift_summary'] ?? '') !== '')
                    <section>
                        <h3 class="mb-2 text-xs font-semibold text-heading">{{ __('Schema drift') }}</h3>
                        <p class="text-sm text-body-subtle">{{ $app['schema_drift_summary'] }}</p>
                    </section>
                @endif

                @if (($app['state'] ?? '') !== 'uninstalled' && ($app['uninstall_blockers'] ?? []) !== [])
                    <section>
                        <h3 class="mb-2 text-xs font-semibold text-heading">{{ __('Uninstall blockers') }}</h3>
                        <ul class="list-inside list-disc space-y-1 text-sm text-body-subtle">
                            @foreach ($app['uninstall_blockers'] as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section>
                    <h3 class="mb-2 text-xs font-semibold text-heading">{{ __('Documentation') }}</h3>
                    @if (($app['description'] ?? '') !== '')
                        <div class="w-full rounded-lg bg-neutral-secondary/50 px-5 py-4 text-sm leading-relaxed whitespace-pre-wrap text-body">
                            {{ $app['description'] }}
                        </div>
                    @else
                        <p class="text-sm text-body-subtle italic">{{ __('No extended description in the module manifest.') }}</p>
                    @endif
                </section>
            </div>
        </div>
    @endif
</div>
