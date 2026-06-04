@php
    $cfg = [
        'wireKey' => $wireKey,
        'readonly' => $readonly ?? false,
        'initial' => (string) ($value ?? ''),
        'placeholder' => $placeholder ?? __('Write here…'),
        'pickerTitle' => __('Choose image'),
    ];
@endphp

@if ($readonly ?? false)
    @include('velm-ui::widgets.display.rich-text', ['value' => $value])
@else
    <div wire:ignore class="pv-rich-text-field" data-pv-rich-text x-data="pvRichText(@js($cfg))">
        <textarea x-ref="wireField" tabindex="-1" aria-hidden="true" class="sr-only"></textarea>

        <div class="pv-rich-text-editor pv-rich-text-editor--simple">
            @include('velm-ui::widgets.partials.rich-text-toolbar')
            <div class="simple-editor-content">
                <div x-ref="mount" class="min-h-[8rem]"></div>
            </div>
        </div>
    </div>
@endif
