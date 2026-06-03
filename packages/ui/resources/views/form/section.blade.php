<fieldset class="rounded-xl border border-default bg-neutral-primary p-6 shadow-sm">
    @if ($section->title !== '')
        <legend class="px-2 text-xs font-semibold uppercase tracking-wider text-body-subtle">
            {{ $section->title }}
        </legend>
    @endif

    @include('velm-ui::form.field-grid', [
        'cells' => $section->cells,
        'cols' => $section->cols,
        'mode' => $mode,
    ])
</fieldset>
