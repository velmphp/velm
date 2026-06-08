@php
    $count = $this->listSelectedCount();
    $actions = $this->listBulkActions();
@endphp

@if ($count > 0 && $actions !== [])
    <div
        class="flex flex-wrap items-center gap-3 rounded-lg border border-fg-brand/30 bg-brand-softer px-4 py-2.5"
        wire:key="list-bulk-bar-{{ $count }}"
    >
        <p class="text-sm font-medium text-heading">
            {{ trans_choice(':count record selected|:count records selected', $count, ['count' => $count]) }}
        </p>

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($actions as $action)
                @include('velm-ui::list.bulk-action', ['action' => $action])
            @endforeach
        </div>

        <button
            type="button"
            wire:click="clearListSelection"
            class="ms-auto text-sm text-fg-brand hover:underline"
        >
            {{ __('Clear selection') }}
        </button>
    </div>
@endif
