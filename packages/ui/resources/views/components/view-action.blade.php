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
    $isInlineForm = $kind === 'inline_form';
    $label = __($action['label']);
@endphp

@if (in_array($kind, ['form', 'inline_form'], true) && filled($action['form_url'] ?? null))
    <button
        type="button"
        onclick="(async () => {
            @if (filled($action['confirm']))
                const ok = await window.pvConfirm(@js($action['confirm']), { title: @js($label), confirmLabel: @js(__('Continue')) });
                if (!ok) return;
            @endif
            const reload = () => window.location.reload();
            if (@js($isInlineForm) && window.PvWorkflowDialog?.openForm) {
                window.PvWorkflowDialog.openForm(@js($action['form_url']), @js($label), reload);
                return;
            }
            window.addEventListener('velm-dialog-saved', reload, { once: true });
            if (window.pvOpenRecord) {
                window.pvOpenRecord(@js($action['form_url']), @js($label));
            } else {
                window.location.href = @js($action['form_url']);
            }
        })()"
        @class([$buttonClass, 'inline-flex items-center gap-1.5'])
    >
        {{ $label }}
    </button>
@elseif ($kind === 'get')
    <a
        href="{{ $action['url'] }}"
        @unless ($action['full_page']) wire:navigate @endunless
        @class([$buttonClass, 'inline-flex items-center gap-1.5'])
    >
        {{ $label }}
    </a>
@else
    <button
        type="button"
        onclick="window.pvExecuteViewAction({
            url: @js($action['url']),
            method: @js(strtoupper($action['method'])),
            label: @js($label),
            confirm: @js(filled($action['confirm']) ? __($action['confirm']) : ''),
            message: @js($label),
        })"
        @class([$buttonClass, 'inline-flex items-center gap-1.5'])
    >
        {{ $label }}
    </button>
@endif
