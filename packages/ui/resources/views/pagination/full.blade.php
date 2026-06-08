@php
    if (! isset($scrollTo)) {
        $scrollTo = '[data-pv-form-shell]';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}'))?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        JS
        : '';

    $group = 'inline-flex items-stretch overflow-hidden rounded-md border border-default shadow-sm divide-x divide-default';
    $cell = 'inline-flex min-w-9 shrink-0 items-center justify-center px-0 py-1.5 text-sm font-medium transition-colors';
    $cellIdle = $cell.' bg-neutral-primary text-body hover:bg-neutral-secondary disabled:cursor-not-allowed disabled:opacity-50';
    $cellActive = $cell.' z-10 bg-brand-soft text-fg-brand';
    $cellDisabled = $cell.' cursor-default bg-neutral-secondary text-body-subtle opacity-70';
    $cellIcon = $cell.' bg-neutral-primary text-body-subtle hover:bg-neutral-secondary disabled:cursor-not-allowed disabled:opacity-50';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-body-subtle">
            <span>{{ __('Showing') }}</span>
            <span class="font-medium text-heading">{{ $paginator->firstItem() }}</span>
            <span>{{ __('to') }}</span>
            <span class="font-medium text-heading">{{ $paginator->lastItem() }}</span>
            <span>{{ __('of') }}</span>
            <span class="font-medium text-heading">{{ $paginator->total() }}</span>
            <span>{{ __('results') }}</span>
        </p>

        <div class="{{ $group }}">
            @if ($paginator->onFirstPage())
                <span class="{{ $cellIcon }} opacity-60" aria-disabled="true" aria-label="{{ __('Previous') }}">
                    <x-velm-ui::icon icon="heroicon-o-chevron-left" class="h-4 w-4 shrink-0" />
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="previousPage{{ $paginator->getPageName() === 'page' ? '' : '.'.$paginator->getPageName() }}"
                    class="{{ $cellIcon }}"
                    aria-label="{{ __('Previous') }}"
                >
                    <x-velm-ui::icon icon="heroicon-o-chevron-left" class="h-4 w-4 shrink-0" />
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $cellDisabled }}" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $cellActive }}">{{ $page }}</span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}"
                                class="{{ $cellIdle }}"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                            >
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="nextPage{{ $paginator->getPageName() === 'page' ? '' : '.'.$paginator->getPageName() }}"
                    class="{{ $cellIcon }}"
                    aria-label="{{ __('Next') }}"
                >
                    <x-velm-ui::icon icon="heroicon-o-chevron-right" class="h-4 w-4 shrink-0" />
                </button>
            @else
                <span class="{{ $cellIcon }} opacity-60" aria-disabled="true" aria-label="{{ __('Next') }}">
                    <x-velm-ui::icon icon="heroicon-o-chevron-right" class="h-4 w-4 shrink-0" />
                </span>
            @endif
        </div>
    </nav>
@endif
