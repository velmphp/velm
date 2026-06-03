@php
    use Velm\Ui\Forms\FormLayoutKind;

    $mode = $this->velmFormMode();
    $sections = $this->velmFormSections();
@endphp

<div class="mx-auto max-w-5xl space-y-6" data-pv-form-shell>
    @include('velm-ui::form.actions-bar', [
        'mode' => $mode,
        'listUrl' => $this->listPageUrl(),
        'editUrl' => method_exists($this, 'velmEditPageUrl') ? $this->velmEditPageUrl() : null,
        'embed' => request()->boolean('embed'),
    ])

    @if ($this->formError)
        <div
            data-pv-form-error
            class="rounded-lg border border-fg-danger/30 bg-danger-soft px-4 py-3 text-sm text-fg-danger"
        >
            <p class="font-semibold">{{ __('Could not save:') }}</p>
            <p>{{ $this->formError }}</p>
        </div>
    @endif

    @if ($mode !== \Velm\Ui\Forms\FormMode::Display)
        <form
            id="velm-form"
            class="space-y-6"
            wire:submit="{{ $mode === \Velm\Ui\Forms\FormMode::New ? 'createVelmForm' : 'saveVelmForm' }}"
        >
            @foreach ($sections as $section)
                @if ($section->kind === FormLayoutKind::Notebook)
                    @include('velm-ui::form.notebook', ['section' => $section, 'mode' => $mode])
                @else
                    @include('velm-ui::form.section', ['section' => $section, 'mode' => $mode])
                @endif
            @endforeach
        </form>
    @else
        <div class="space-y-6">
            @foreach ($sections as $section)
                @if ($section->kind === FormLayoutKind::Notebook)
                    @include('velm-ui::form.notebook', ['section' => $section, 'mode' => $mode])
                @else
                    @include('velm-ui::form.section', ['section' => $section, 'mode' => $mode])
                @endif
            @endforeach
        </div>
    @endif
</div>
