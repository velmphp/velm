@php
    $cfg = [
        'wireKey' => $wireKey,
        'readonly' => $readonly ?? false,
        'initial' => (string) ($value ?? ''),
        'language' => $codeLanguage ?? 'json',
    ];
@endphp

@if ($readonly ?? false)
    @include('velm-ui::widgets.display.code', ['value' => $value, 'codeLanguage' => $codeLanguage ?? 'json'])
@else
    <div wire:ignore class="pv-code-editor" data-pv-code-editor x-data="pvCodeEditor(@js($cfg))">
        <textarea x-ref="wireField" tabindex="-1" aria-hidden="true" class="sr-only"></textarea>
        <div
            x-ref="mount"
            class="pv-code-editor__host overflow-hidden rounded-md border border-default"
            data-language="{{ $codeLanguage ?? 'json' }}"
        ></div>
    </div>
@endif
