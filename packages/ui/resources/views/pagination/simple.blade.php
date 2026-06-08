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
    $cell = 'inline-flex shrink-0 items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium transition-colors';
    $cellIdle = $cell.' bg-neutral-primary text-body hover:bg-neutral-secondary disabled:cursor-not-allowed disabled:opacity-50';
    $cellDisabled = $cell.' cursor-default bg-neutral-secondary text-body-subtle opacity-60';
    $cellInfo = $cell.' cursor-default bg-neutral-primary text-body-subtle';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="{{ $group }}">
            @if ($paginator->onFirstPage())
                <span class="{{ $cellDisabled }}" aria-disabled="true">
                    <x-velm-ui::icon icon="heroicon-o-chevron-left" class="h-4 w-4 shrink-0" />
                    {{ __('Previous') }}
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="previousPage{{ $paginator->getPageName() === 'page' ? '' : '.'.$paginator->getPageName() }}"
                    class="{{ $cellIdle }}"
                    aria-label="{{ __('Previous') }}"
                >
                    <x-velm-ui::icon icon="heroicon-o-chevron-left" class="h-4 w-4 shrink-0" />
                    {{ __('Previous') }}
                </button>
            @endif

            <span class="{{ $cellInfo }}">
                {{ __('Page :current of :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
            </span>

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="nextPage{{ $paginator->getPageName() === 'page' ? '' : '.'.$paginator->getPageName() }}"
                    class="{{ $cellIdle }}"
                    aria-label="{{ __('Next') }}"
                >
                    {{ __('Next') }}
                    <x-velm-ui::icon icon="heroicon-o-chevron-right" class="h-4 w-4 shrink-0" />
                </button>
            @else
                <span class="{{ $cellDisabled }}" aria-disabled="true">
                    {{ __('Next') }}
                    <x-velm-ui::icon icon="heroicon-o-chevron-right" class="h-4 w-4 shrink-0" />
                </span>
            @endif
        </div>
    </nav>
@endif
