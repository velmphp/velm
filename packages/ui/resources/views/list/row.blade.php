@php
    /** @var array<string, mixed> $record */
    $recordId = (int) ($record['id'] ?? 0);
    $openUrl = $this->listOpenUrl($recordId);
    $rowActions = $this->listRowActions();
@endphp

<tr
    @if (! empty($xShow ?? null)) x-show="{{ $xShow }}" x-cloak @endif
    @if ($openUrl)
        class="cursor-pointer hover:bg-neutral-secondary/50"
        @click="if (! $event.defaultPrevented) { (window.Livewire && typeof Livewire.navigate === 'function') ? Livewire.navigate(@js($openUrl)) : (window.location.href = @js($openUrl)) }"
    @endif
>
    @if ($showsSelection ?? false)
        <td class="w-9 px-3 py-3" @click.stop>
            <input
                type="checkbox"
                class="h-4 w-4 rounded border-default text-fg-brand focus:ring-fg-brand/40"
                wire:model.live="listSelectedIds"
                value="{{ $recordId }}"
                aria-label="{{ __('Select record') }}"
            />
        </td>
    @endif
    @foreach ($columns as $column)
        @if ($this->isListColumnVisible($column->name))
            <td class="px-4 py-3 text-body">
                @if ($column->kind === 'toggle')
                    @php $isOn = (bool) ($record[$column->name] ?? false); @endphp
                    <div @click.stop>
                    <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $isOn ? 'true' : 'false' }}"
                        wire:click="updateListToggle({{ $recordId }}, '{{ $column->name }}', {{ $isOn ? 'false' : 'true' }})"
                        class="cursor-pointer align-middle"
                    >
                        <span @class(['pv-list-toggle', 'pv-list-toggle--on' => $isOn]) aria-hidden="true"></span>
                    </button>
                    </div>
                @else
                    {{ $this->formatListCell($column, $record[$column->name] ?? null) }}
                @endif
            </td>
        @endif
    @endforeach
    @if ($rowActions !== [])
        <td class="px-4 py-3 text-right whitespace-nowrap" @click.stop>
            <div class="inline-flex flex-wrap items-center justify-end gap-1">
                @foreach ($rowActions as $action)
                    @if ($this->listRowActionUsesWire($action))
                        <button
                            type="button"
                            wire:click="deleteListRecord({{ $recordId }})"
                            wire:confirm="{{ __('Delete this record? This cannot be undone.') }}"
                            title="{{ __($action['label']) }}"
                            class="pv-row-action pv-row-action--danger inline-flex shrink-0 items-center justify-center rounded-md p-1.5 transition-colors"
                        >
                            <x-velm-ui::icon :icon="$action['icon']" class="h-4 w-4 shrink-0" />
                            <span class="sr-only">{{ __($action['label']) }}</span>
                        </button>
                    @else
                        @php $actionUrl = $this->listRowActionUrl($action, $recordId); @endphp
                        @if ($actionUrl)
                            <x-velm-ui::action-link
                                :href="$actionUrl"
                                :icon="$action['icon']"
                                :label="__($action['label'])"
                            />
                        @endif
                    @endif
                @endforeach
            </div>
        </td>
    @endif
</tr>
