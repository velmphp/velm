<div class="pv-workflow-status-strip">
    <template x-if="loading">
        <p class="px-1 text-sm text-body-subtle">{{ __('Loading workflow…') }}</p>
    </template>

    <template x-if="!loading && ctx && ctx.has_workflow && ctx.statusbar && ctx.statusbar.length">
        <ol class="pv-workflow-statusbar">
            <template x-for="step in ctx.statusbar" :key="step.key">
                <li
                    class="pv-workflow-status-pill"
                    :class="{
                        'pv-workflow-status-pill--current': step.current && !step.cancelled,
                        'pv-workflow-status-pill--cancelled': step.current && step.cancelled,
                        'pv-workflow-status-pill--done': step.done && !step.current,
                        'pv-workflow-status-pill--upcoming': !step.current && !step.done,
                    }"
                    x-text="step.label"
                ></li>
            </template>
        </ol>
    </template>
</div>
