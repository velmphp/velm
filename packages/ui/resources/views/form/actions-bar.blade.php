@php
    use Velm\Ui\Forms\FormMode;

    $embed = $embed ?? false;
    $editUrl = $editUrl ?? null;

    if ($embed && is_string($editUrl) && $editUrl !== '' && ! str_contains($editUrl, 'embed=1')) {
        $editUrl .= str_contains($editUrl, '?') ? '&' : '?';
        $editUrl .= 'embed=1';
    }
@endphp

<div
    class="pv-form-actions-bar sticky top-0 z-20 -mx-1 mb-2 flex items-center justify-between gap-3 border-b border-default/80 bg-neutral-primary/95 px-1 py-2 backdrop-blur-sm"
>
    <span @class([
        'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-2xs font-semibold uppercase tracking-wider',
        'bg-warning-soft text-warning-strong' => $mode === FormMode::Edit,
        'bg-success-soft text-success-strong' => $mode === FormMode::New,
        'bg-neutral-tertiary text-body-subtle' => $mode === FormMode::Display,
    ])>
        {{ $mode->value }}
    </span>

    <div class="flex items-center gap-2">
        @if ($mode === FormMode::Display && filled($editUrl))
            <a
                href="{{ $editUrl }}"
                @unless ($embed) wire:navigate @endunless
                class="pv-btn inline-flex items-center gap-1.5 rounded-md bg-fg-brand px-3 py-1.5 text-sm font-medium text-white transition hover:opacity-90"
            >
                <x-velm-ui::icon icon="heroicon-o-pencil-square" class="h-4 w-4 shrink-0" />
                {{ __('Edit') }}
            </a>
        @endif

        @if ($mode === FormMode::New)
            <x-velm-ui::action-button
                type="submit"
                form="velm-form"
                icon="heroicon-o-plus"
                :label="__('Create')"
                class="bg-success text-white hover:opacity-90"
                wire:loading.attr="disabled"
            />
        @elseif ($mode === FormMode::Edit)
            <x-velm-ui::action-button
                type="submit"
                form="velm-form"
                icon="heroicon-o-check"
                :label="__('Save')"
                class="bg-success text-white hover:opacity-90"
                wire:loading.attr="disabled"
            />
        @endif

        @if (in_array($mode, [FormMode::Display, FormMode::Edit], true) && $this->velmFormCanDelete())
            <button
                type="button"
                class="pv-btn inline-flex items-center gap-1.5 rounded-md border border-default px-3 py-1.5 text-sm text-fg-danger transition hover:bg-danger-soft"
                wire:click="deleteVelmForm"
                wire:confirm="{{ __('Delete this record? This cannot be undone.') }}"
                wire:loading.attr="disabled"
            >
                <x-velm-ui::icon icon="heroicon-o-trash" class="h-4 w-4 shrink-0" />
                {{ __('Delete') }}
            </button>
        @endif

        @if ($embed)
            <button
                type="button"
                class="pv-btn inline-flex items-center gap-1.5 rounded-md border border-default bg-neutral-primary px-3 py-1.5 text-sm text-body transition hover:bg-neutral-secondary"
                onclick="window.parent.pvCloseRecordDialog?.()"
            >
                <x-velm-ui::icon icon="heroicon-o-x-mark" class="h-4 w-4 shrink-0" />
                {{ $mode === FormMode::Display ? __('Close') : __('Cancel') }}
            </button>
        @else
            <button
                type="button"
                class="pv-btn inline-flex items-center gap-1.5 rounded-md border border-default bg-neutral-primary px-3 py-1.5 text-sm text-body transition hover:bg-neutral-secondary"
                @click="window.VelmNav?.goBack(@js($listUrl))"
            >
                <x-velm-ui::icon icon="heroicon-o-arrow-left" class="h-4 w-4 shrink-0" />
                {{ $mode === FormMode::Display ? __('Back') : __('Cancel') }}
            </button>
        @endif
    </div>
</div>
