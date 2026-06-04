@if (filled($value ?? null))
    <pre class="pv-code-display overflow-x-auto rounded-md border border-default bg-neutral-secondary-soft p-3 text-xs leading-relaxed text-body"><code>{{ $value }}</code></pre>
@else
    <p class="text-sm text-body-subtle">—</p>
@endif
