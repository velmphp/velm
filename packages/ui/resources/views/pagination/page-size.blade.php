<label class="inline-flex items-center gap-2 text-sm text-body-subtle">
    <span>{{ __('Per page') }}</span>
    <select wire:model.live="listPerPage" class="pv-input-sm w-auto min-w-20">
        @foreach ($this->listPageSizeOptions() as $option)
            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
        @endforeach
    </select>
</label>
