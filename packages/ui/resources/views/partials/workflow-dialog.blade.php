<div
    x-show="$store.workflowDialog.isOpen"
    x-cloak
    class="pv-record-dialog-root"
    :class="{ 'pv-record-dialog-root--open': $store.workflowDialog.isOpen }"
    @keydown.escape.window="$store.workflowDialog.close()"
>
    <div
        x-show="$store.workflowDialog.isOpen"
        x-transition.opacity
        class="pv-record-dialog-backdrop"
        @click="$store.workflowDialog.close()"
    ></div>
    <div
        x-show="$store.workflowDialog.isOpen"
        x-transition
        class="pv-record-dialog-panel max-w-lg w-full"
        :style="$store.workflowDialog.panelStyle()"
        @click.stop
    >
        <div
            class="pv-record-dialog-header cursor-move"
            @mousedown="$store.workflowDialog.startDrag($event)"
        >
            <span class="pv-record-dialog-title" x-text="$store.workflowDialog.title || '{{ __('Workflow') }}'"></span>
            <button
                type="button"
                class="pv-record-dialog-close"
                @click="$store.workflowDialog.close()"
                aria-label="{{ __('Close') }}"
            >&times;</button>
        </div>
        <div class="pv-record-dialog-body p-4" id="pv-workflow-dialog-body"></div>
    </div>
</div>
