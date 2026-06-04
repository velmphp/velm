@php
    use Velm\Ui\Forms\FormLayoutKind;
    use Velm\Ui\Forms\FormMode;

    $mode = $this->velmFormMode();
    $sections = $this->velmFormSections();
    $workflowOnRecord = $mode === FormMode::Display
        && method_exists($this, 'velmWorkflowModel')
        && $this->velmWorkflowModel() !== ''
        && $this->velmWorkflowRecordId() > 0;
@endphp

<div
    @class([
        'mx-auto space-y-6',
        'max-w-5xl' => ! $workflowOnRecord,
        'max-w-[90rem]' => $workflowOnRecord,
    ])
    data-pv-form-shell
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.page-heading')

    @if ($workflowOnRecord)
        @include('velm-ui::workflow.panel', [
            'workflowModel' => $this->velmWorkflowModel(),
            'workflowRecordId' => $this->velmWorkflowRecordId(),
        ])
    @endif

    @include('velm-ui::form.actions-bar', [
        'mode' => $mode,
        'listUrl' => $this->listPageUrl(),
        'editUrl' => method_exists($this, 'velmEditPageUrl') ? $this->velmEditPageUrl() : null,
        'embed' => request()->boolean('embed'),
    ])

    @if ($workflowOnRecord)
        <div
            class="pv-workflow-record"
            data-pv-workflow
            data-res-model="{{ $this->velmWorkflowModel() }}"
            data-res-id="{{ $this->velmWorkflowRecordId() }}"
            x-data="pvWorkflowBar()"
            x-init="load()"
        >
            @include('velm-ui::workflow.panel-status')

            <div class="pv-workflow-record__body">
                <div class="pv-workflow-record__main min-w-0 flex-1 space-y-6">
                    @if ($this->formError)
                        <div
                            data-pv-form-error
                            class="rounded-lg border border-fg-danger/30 bg-danger-soft px-4 py-3 text-sm text-fg-danger"
                        >
                            <p class="font-semibold">{{ __('Could not save:') }}</p>
                            <p>{{ $this->formError }}</p>
                        </div>
                    @endif

                    @foreach ($sections as $section)
                        @if ($section->kind === FormLayoutKind::Notebook)
                            @include('velm-ui::form.notebook', ['section' => $section, 'mode' => $mode])
                        @else
                            @include('velm-ui::form.section', ['section' => $section, 'mode' => $mode])
                        @endif
                    @endforeach
                </div>

                @include('velm-ui::workflow.panel-aside')
            </div>
        </div>
    @else
        @if ($this->formError)
            <div
                data-pv-form-error
                class="rounded-lg border border-fg-danger/30 bg-danger-soft px-4 py-3 text-sm text-fg-danger"
            >
                <p class="font-semibold">{{ __('Could not save:') }}</p>
                <p>{{ $this->formError }}</p>
            </div>
        @endif

        @if ($mode !== FormMode::Display)
            <form
                id="velm-form"
                class="space-y-6"
                wire:submit="{{ $mode === FormMode::New ? 'createVelmForm' : 'saveVelmForm' }}"
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
    @endif
</div>
