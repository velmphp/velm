@php
    $defaultTab = $section->pages[0]->name ?? '';
    $pageNames = array_map(static fn ($p) => $p->name, $section->pages);
@endphp

<fieldset
    class="rounded-xl border border-default bg-neutral-primary p-6 shadow-sm"
    x-data="pvFormNotebook(@js($section->storageKey), @js($defaultTab), @js($pageNames))"
>
    @if ($section->title !== '')
        <legend class="px-2 text-xs font-semibold uppercase tracking-wider text-body-subtle">
            {{ $section->title }}
        </legend>
    @endif

    <div class="mb-4 border-b border-default" aria-label="{{ $section->title }}">
        <ul class="flex flex-wrap -mb-px text-center text-sm font-medium" role="tablist">
            @foreach ($section->pages as $page)
                <li class="@if (! $loop->last) me-2 @endif" role="presentation">
                    <button
                        type="button"
                        role="tab"
                        :id="'tab-' + @js($page->name)"
                        :aria-selected="tab === @js($page->name)"
                        @click="pick(@js($page->name))"
                        class="inline-block rounded-t-base border-b-2 p-4 transition-colors"
                        :class="tab === @js($page->name) ? 'border-brand text-fg-brand' : 'border-transparent text-body hover:border-brand hover:text-fg-brand'"
                    >
                        {{ $page->title }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    @foreach ($section->pages as $page)
        <div
            role="tabpanel"
            x-show="tab === @js($page->name)"
            class="pt-2"
        >
            @include('velm-ui::form.field-grid', [
                'cells' => $page->cells,
                'cols' => $page->cols,
                'mode' => $mode,
            ])
        </div>
    @endforeach
</fieldset>
