<div
    x-data
    x-show="$store.recordDialog.isOpen"
    x-cloak
    class="pv-record-dialog-root"
    :class="{ 'pv-record-dialog-root--open': $store.recordDialog.isOpen }"
    @keydown.escape.window="$store.recordDialog.close()"
>
    <div
        x-show="$store.recordDialog.isOpen"
        x-transition.opacity
        class="pv-record-dialog-backdrop"
        @click="$store.recordDialog.close()"
    ></div>

    <div
        x-show="$store.recordDialog.isOpen"
        x-transition
        class="pv-record-dialog-panel"
        :style="$store.recordDialog.panelStyle()"
        role="dialog"
        aria-modal="true"
        :aria-label="$store.recordDialog.title || '{{ __('Record') }}'"
    >
        <div
            class="pv-record-dialog-header"
            @mousedown="$store.recordDialog.startDrag($event)"
        >
            <span class="pv-record-dialog-title" x-text="$store.recordDialog.title || '{{ __('Record') }}'"></span>
            <div class="flex shrink-0 items-center gap-1">
                <a
                    x-show="$store.recordDialog.fullPageUrl"
                    :href="$store.recordDialog.fullPageUrl"
                    target="_top"
                    class="pv-record-dialog-fullpage"
                    @mousedown.stop
                >
                    {{ __('Open full page') }}
                </a>
                <button
                    type="button"
                    class="pv-record-dialog-close"
                    @click="$store.recordDialog.close()"
                    @mousedown.stop
                    aria-label="{{ __('Close') }}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="pv-record-dialog-body">
            <iframe
                x-show="$store.recordDialog.iframeUrl"
                :src="$store.recordDialog.iframeUrl"
                class="pv-record-dialog-iframe"
                title=""
            ></iframe>
        </div>
    </div>
</div>
