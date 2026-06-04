@if (filled($value ?? null))
    <div class="pv-rich-text-display pv-rich-text-display--simple">
        <div class="tiptap simple-editor">{!! $value !!}</div>
    </div>
@else
    <p class="text-sm text-body-subtle">—</p>
@endif
