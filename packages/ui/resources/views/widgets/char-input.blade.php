<input
    type="text"
    wire:model="data.{{ $name }}"
    @readonly($readonly ?? false)
    @required($required ?? false)
    class="pv-input"
/>
