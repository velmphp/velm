<label class="relative inline-flex cursor-pointer items-center">
    <input type="checkbox" wire:model.live="data.{{ $name }}" class="peer sr-only" @disabled($readonly ?? false) />
    <span
        class="relative inline-block h-5 w-9 rounded-full bg-neutral-tertiary transition-colors
               after:absolute after:start-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform
               peer-checked:bg-fg-brand peer-checked:after:translate-x-4"
        aria-hidden="true"
    ></span>
    <span class="ms-2 text-sm text-body peer-checked:hidden">{{ __('No') }}</span>
    <span class="ms-2 hidden text-sm text-body peer-checked:inline">{{ __('Yes') }}</span>
</label>
