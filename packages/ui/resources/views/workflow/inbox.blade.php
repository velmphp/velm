<div data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}">
    <div class="mx-auto max-w-4xl space-y-6">
        <header>
            <h1 class="text-lg font-semibold tracking-tight text-heading">{{ __('My approvals') }}</h1>
            <p class="mt-1 text-sm text-body-subtle">{{ __('Pending workflow approvals assigned to you.') }}</p>
        </header>

        @if ($items === [])
            <p class="rounded-lg border border-default bg-neutral-primary-soft px-4 py-8 text-center text-sm text-body-subtle">
                {{ __('No pending approvals.') }}
            </p>
        @else
            <ul class="divide-y divide-default rounded-xl border border-default bg-neutral-primary-soft">
                @foreach ($items as $item)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-4">
                        <div>
                            <p class="font-medium text-heading">{{ $item['record_label'] }}</p>
                            <p class="text-sm text-body-subtle">
                                {{ $item['transition_label'] }} · {{ $item['state_label'] }}
                            </p>
                            @if (! empty($item['deadline_at']))
                                <p class="text-xs text-warning">{{ __('Deadline:') }} {{ $item['deadline_at'] }}</p>
                            @endif
                        </div>
                        @if (! empty($item['form_href']))
                            <a href="{{ $item['form_href'] }}" class="pv-btn pv-btn-primary pv-btn-sm">{{ __('Open record') }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
