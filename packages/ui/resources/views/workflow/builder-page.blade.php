@php
    /** @var array<string, mixed> $config */
@endphp

<div
    class="pv-workflow-builder mx-auto flex max-w-6xl flex-col gap-5"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
    x-data="pvWorkflowBuilder(window._pvWorkflowBuilderCfg)"
>
    @push('before-livewire')
        <script>window._pvWorkflowBuilderCfg = @json($config);</script>
        <script defer src="{{ \Velm\Ui\UiAssets::workflowBuilderScriptHref() }}"></script>
    @endpush

    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-default pb-4">
        <div>
            <a href="/velm/views/workflow/workflow_definition.list" class="text-sm text-fg-brand hover:underline">{{ __('Workflow definitions') }}</a>
            <h1 class="mt-1 text-lg font-semibold tracking-tight text-heading">{{ $this->getTitle() }}</h1>
            <p class="text-sm text-body-subtle">{{ __('Design approvals and stage forms without code') }}</p>
        </div>
        <button type="button" class="pv-btn pv-btn-primary" @click="save()" :disabled="saving">
            <span x-text="saving ? '{{ __('Saving…') }}' : '{{ __('Save workflow') }}'"></span>
        </button>
    </header>

    <p class="text-sm text-fg-success" x-show="saveMessage" x-text="saveMessage"></p>
    <p class="text-sm text-fg-danger" x-show="saveError" x-text="saveError"></p>

    <nav class="pv-workflow-builder__steps">
        <template x-for="(s, i) in steps" :key="s.id">
            <button
                type="button"
                @click="goStep(s.id)"
                class="pv-workflow-builder__step"
                :class="{ 'pv-workflow-builder__step--active': step === s.id }"
            >
                <span class="mr-1 opacity-60" x-text="i + 1"></span>
                <span x-text="s.label"></span>
            </button>
        </template>
    </nav>

    <section x-show="step === 1" x-cloak class="pv-workflow-builder__panel">
        <h3 class="text-base font-semibold text-heading">{{ __('Basics') }}</h3>
        <label class="block max-w-md text-sm">
            <span class="font-medium text-heading">{{ __('Name') }}</span>
            <input type="text" x-model="meta.name" class="pv-input mt-1">
        </label>
        <label class="block max-w-md text-sm">
            <span class="font-medium text-heading">{{ __('Description') }}</span>
            <input type="text" x-model="meta.description" class="pv-input mt-1">
        </label>
        <label class="block max-w-md text-sm">
            <span class="font-medium text-heading">{{ __('Record type') }}</span>
            <select x-model="meta.model" @change="onModelChange()" class="pv-input mt-1">
                <option value="">{{ __('— choose —') }}</option>
                <template x-for="m in models" :key="m.value">
                    <option :value="m.value" x-text="m.label"></option>
                </template>
            </select>
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-body">
            <input type="checkbox" x-model="meta.active" class="pv-checkbox">
            <span>{{ __('Active (one active workflow per model)') }}</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-body">
            <input type="checkbox" x-model="definition.auto_start" class="pv-checkbox">
            <span>{{ __('Auto-start on new records') }}</span>
        </label>
    </section>

    <section x-show="step === 2" x-cloak class="pv-workflow-builder__panel">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-heading">{{ __('States') }}</h3>
            <button type="button" class="pv-btn pv-btn-sm pv-btn-secondary" @click="addState()">{{ __('Add state') }}</button>
        </div>
        <template x-for="(st, i) in definition.states" :key="st._uid">
            <div class="flex flex-wrap items-end gap-2 rounded-lg border border-default bg-neutral-secondary-soft p-3">
                <label class="min-w-[120px] flex-1 text-sm">
                    <span class="text-xs text-body-subtle">{{ __('Key') }}</span>
                    <input type="text" x-model="st.key" class="pv-input-sm mt-0.5">
                </label>
                <label class="min-w-[160px] flex-1 text-sm">
                    <span class="text-xs text-body-subtle">{{ __('Label') }}</span>
                    <input type="text" x-model="st.label" class="pv-input-sm mt-0.5">
                </label>
                <label class="inline-flex items-center gap-1 text-sm text-body">
                    <input type="radio" name="initial" :value="st._uid" @change="setInitial(st._uid)" class="pv-checkbox">
                    {{ __('Initial') }}
                </label>
                <label class="inline-flex items-center gap-1 text-sm text-body">
                    <input type="checkbox" x-model="st.final" class="pv-checkbox">
                    {{ __('Final') }}
                </label>
                <label class="inline-flex items-center gap-1 text-sm text-body">
                    <input type="checkbox" x-model="st.cancelled" class="pv-checkbox">
                    {{ __('Cancelled') }}
                </label>
                <button type="button" class="text-sm text-fg-danger hover:underline" @click="removeState(i)">{{ __('Remove') }}</button>
            </div>
        </template>
    </section>

    <section x-show="step === 3" x-cloak class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-heading">{{ __('Transitions') }}</h3>
            <button type="button" class="pv-btn pv-btn-sm pv-btn-secondary" @click="addTransition()">{{ __('Add transition') }}</button>
        </div>
        <template x-for="(tr, ti) in definition.transitions" :key="tr._uid">
            <div class="pv-workflow-builder__panel space-y-3">
                <div class="flex flex-wrap items-end gap-2">
                    <label class="min-w-[100px] text-sm">
                        <span class="text-xs text-body-subtle">{{ __('Key') }}</span>
                        <input type="text" x-model="tr.key" class="pv-input-sm mt-0.5">
                    </label>
                    <label class="min-w-[140px] flex-1 text-sm">
                        <span class="text-xs text-body-subtle">{{ __('Button label') }}</span>
                        <input type="text" x-model="tr.label" class="pv-input-sm mt-0.5">
                    </label>
                    <label class="text-sm">
                        <span class="text-xs text-body-subtle">{{ __('From') }}</span>
                        <select x-model="tr.fromSingle" class="pv-input-sm mt-0.5">
                            <template x-for="st in definition.states" :key="st.key">
                                <option :value="st.key" x-text="st.label"></option>
                            </template>
                        </select>
                    </label>
                    <label class="text-sm">
                        <span class="text-xs text-body-subtle">{{ __('To') }}</span>
                        <select x-model="tr.to" class="pv-input-sm mt-0.5">
                            <template x-for="st in definition.states" :key="st.key">
                                <option :value="st.key" x-text="st.label"></option>
                            </template>
                        </select>
                    </label>
                    <label class="text-sm">
                        <span class="text-xs text-body-subtle">{{ __('Kind') }}</span>
                        <select x-model="tr.kind" class="pv-input-sm mt-0.5">
                            <option value="user">{{ __('User action') }}</option>
                            <option value="approval">{{ __('Requires approval') }}</option>
                        </select>
                    </label>
                    <button type="button" class="text-sm text-fg-danger hover:underline" @click="removeTransition(ti)">{{ __('Remove') }}</button>
                </div>
                <div x-show="tr.kind === 'approval'" class="pv-workflow-builder__approval">
                    <select x-model="tr.approval.strategy" class="pv-input-sm">
                        <option value="any">{{ __('Any approver') }}</option>
                        <option value="all">{{ __('All approvers') }}</option>
                        <option value="sequential">{{ __('Sequential') }}</option>
                    </select>
                    <select x-model="tr.approval.assignee_type" class="pv-input-sm">
                        <option value="group">{{ __('Group') }}</option>
                        <option value="user">{{ __('User') }}</option>
                        <option value="field">{{ __('Record field') }}</option>
                    </select>
                    <select x-show="tr.approval.assignee_type === 'group'" x-model.number="tr.approval.group_id" class="pv-input-sm max-w-md">
                        <option :value="null">{{ __('Admin (default)') }}</option>
                        <template x-for="g in groups" :key="g.id">
                            <option :value="g.id" x-text="g.name"></option>
                        </template>
                    </select>
                    <input type="number" x-model.number="tr.approval.deadline_hours" placeholder="{{ __('Deadline hours') }}" class="pv-input-sm w-28">
                    <select x-model="tr.reject_to" class="pv-input-sm">
                        <template x-for="st in definition.states" :key="st.key">
                            <option :value="st.key" x-text="st.label"></option>
                        </template>
                    </select>
                </div>
                <div class="border-t border-default pt-3">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-heading">{{ __('Stage form fields') }}</span>
                        <button type="button" class="pv-btn pv-btn-sm pv-btn-secondary" @click="addFormField(tr)">{{ __('Add field') }}</button>
                    </div>
                    <template x-for="(ff, fi) in (tr.form.fields || [])" :key="ff._uid">
                        <div class="mb-2 flex flex-wrap items-end gap-2 text-sm">
                            <select x-model="ff.source" @change="onFieldSourceChange(ff)" class="pv-input-sm">
                                <option value="stage">{{ __('Workflow field') }}</option>
                                <option value="record">{{ __('Record field') }}</option>
                            </select>
                            <template x-if="ff.source === 'record'">
                                <select x-model="ff.name" class="pv-input-sm min-w-[140px]">
                                    <template x-for="rf in recordFields" :key="rf.name">
                                        <option :value="rf.name" x-text="rf.label"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="ff.source !== 'record'">
                                <input type="text" x-model="ff.name" placeholder="key" class="pv-input-sm w-28">
                                <input type="text" x-model="ff.label" placeholder="{{ __('Label') }}" class="pv-input-sm min-w-[120px] flex-1">
                                <select x-model="ff.type" class="pv-input-sm">
                                    <option value="char">{{ __('Text') }}</option>
                                    <option value="text">{{ __('Long text') }}</option>
                                </select>
                            </template>
                            <label class="inline-flex items-center gap-1 text-body">
                                <input type="checkbox" x-model="ff.required" class="pv-checkbox">
                                {{ __('Required') }}
                            </label>
                            <button type="button" class="text-fg-danger hover:underline" @click="tr.form.fields.splice(fi, 1)">×</button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </section>

    <section x-show="step === 4" x-cloak class="pv-workflow-builder__panel">
        <h3 class="mb-3 text-base font-semibold text-heading">{{ __('JSON preview') }}</h3>
        <pre class="max-h-96 overflow-auto rounded-md border border-default bg-neutral-secondary-soft p-3 font-mono text-xs text-body" x-text="JSON.stringify(buildDefinition(), null, 2)"></pre>
    </section>
</div>
