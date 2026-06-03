@php
    use Velm\Ui\Forms\FormMode;

    $gridCols = max(1, (int) ($cols ?: 2));
    $gridColsMd = min($gridCols, max(1, $gridCols > 3 ? 2 : $gridCols));
@endphp

<div
    class="pv-form-grid"
    style="--pv-cols: {{ $gridCols }}; --pv-cols-md: {{ $gridColsMd }};"
>
    @foreach ($cells as $cell)
        @php
            $cellSpan = $cell->wide ? $gridCols : $cell->colspan;
        @endphp

        <div
            data-field="{{ $cell->name }}"
            @if ($cell->error) data-pv-field-error @endif
            @class([
                'flex min-w-0 flex-col gap-2',
                'pv-cell-error' => $cell->error !== null,
            ])
            style="--cell-span: {{ $cellSpan }};"
        >
            <label class="block text-sm font-medium text-heading">
                {{ $cell->label }}
                @if ($mode !== FormMode::Display && $cell->required)
                    <span class="ml-0.5 text-fg-danger" aria-hidden="true">*</span>
                @endif
            </label>

            <div class="text-sm text-body">
                @include($cell->widget, $cell->widgetProps)
            </div>

            @if ($cell->error)
                <p class="flex items-center gap-1 text-xs text-fg-danger">
                    {{ $cell->error }}
                </p>
            @endif
        </div>
    @endforeach
</div>
