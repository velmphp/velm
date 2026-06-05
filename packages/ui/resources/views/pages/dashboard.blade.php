@php
    $widgets = $this->widgets();
@endphp

<div
    class="space-y-4"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    <div>
        <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
        <p class="mt-1 text-sm text-body-subtle">
            {{ __('Overview of your workspace — widgets appear based on installed modules and your access rights.') }}
        </p>
    </div>

    @if ($widgets === [])
        <div class="rounded-lg border border-dashed border-default bg-neutral-primary px-6 py-10 text-center">
            <p class="text-sm font-medium text-heading">{{ __('No dashboard widgets available') }}</p>
            <p class="mt-1 text-sm text-body-subtle">
                {{ __('Install modules from Apps or ask an administrator for access to business data.') }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($widgets as $widget)
                @php
                    $size = (string) ($widget['size'] ?? 'half');
                    $span = match ($size) {
                        'full' => 'md:col-span-2 xl:col-span-3',
                        'third' => 'xl:col-span-1',
                        default => 'md:col-span-1',
                    };
                @endphp
                <div @class(['min-w-0', $span])>
                    @include($widget['view'], ['widget' => $widget])
                </div>
            @endforeach
        </div>
    @endif
</div>
