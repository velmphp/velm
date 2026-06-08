@php
    $rawLanguage = is_string($codeLanguage ?? null) && $codeLanguage !== '' ? $codeLanguage : 'json';
    $prismLanguage = match ($rawLanguage) {
        'js' => 'javascript',
        'html' => 'markup',
        'text' => null,
        default => $rawLanguage,
    };
@endphp

@if (filled($value ?? null))
    <pre
        @class([
            'pv-code-display overflow-x-auto rounded-md border border-default bg-neutral-secondary-soft p-3 text-xs leading-relaxed',
            'language-'.$prismLanguage => filled($prismLanguage),
        ])
        data-pv-code-display
    ><code @if (filled($prismLanguage)) class="language-{{ $prismLanguage }}" @endif>{{ $value }}</code></pre>
@else
    <p class="text-sm text-body-subtle">—</p>
@endif
