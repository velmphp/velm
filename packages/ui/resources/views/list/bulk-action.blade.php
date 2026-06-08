@php
    use Velm\Views\Authoring\ActionVariant;

    $variant = ActionVariant::tryFrom((string) ($action['variant'] ?? '')) ?? ActionVariant::Secondary;
    $buttonClass = match ($variant) {
        ActionVariant::Primary => 'pv-btn pv-btn-primary',
        ActionVariant::Success => 'pv-btn pv-btn-success',
        ActionVariant::Warning => 'pv-btn pv-btn-warning',
        ActionVariant::Danger => 'pv-btn pv-btn-danger',
        ActionVariant::Secondary => 'pv-btn pv-btn-secondary',
    };
    $kind = (string) ($action['kind'] ?? 'post');
    $label = __($action['label']);
    $actionKey = (string) ($action['action_key'] ?? '');
    $confirm = filled($action['confirm'] ?? null) ? __($action['confirm']) : '';
@endphp

@if ($kind === 'wire' && $actionKey !== '')
    <button
        type="button"
        wire:click="runListBulkWireAction('{{ $actionKey }}')"
        @if ($confirm !== '')
            wire:confirm="{{ $confirm }}"
        @endif
        wire:loading.attr="disabled"
        wire:target="runListBulkWireAction"
        @class([$buttonClass, 'inline-flex items-center gap-1.5 text-sm'])
    >
        {{ $label }}
    </button>
@elseif ($kind === 'get' && filled($action['url'] ?? null))
    <button
        type="button"
        onclick="window.pvExecuteBulkViewAction({
            url: @js($action['url']),
            method: 'GET',
            ids: @js($this->listSelectedIds),
            label: @js($label),
            confirm: @js($confirm),
        })"
        @class([$buttonClass, 'inline-flex items-center gap-1.5 text-sm'])
    >
        {{ $label }}
    </button>
@elseif (filled($action['url'] ?? null))
    <button
        type="button"
        onclick="window.pvExecuteBulkViewAction({
            url: @js($action['url']),
            method: @js(strtoupper($action['method'] ?? 'POST')),
            ids: @js($this->listSelectedIds),
            label: @js($label),
            confirm: @js($confirm),
        })"
        @class([$buttonClass, 'inline-flex items-center gap-1.5 text-sm'])
    >
        {{ $label }}
    </button>
@endif
