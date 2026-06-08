<div
    x-show="$store.confirmDialog.isOpen"
    x-cloak
    class="pv-record-dialog-root pv-confirm-dialog-root"
    :class="{ 'pv-record-dialog-root--open': $store.confirmDialog.isOpen }"
    @keydown.escape.window="$store.confirmDialog.dismiss()"
>
    <div
        x-show="$store.confirmDialog.isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="pv-record-dialog-backdrop"
        @click="$store.confirmDialog.dismiss()"
    ></div>
    <div
        x-show="$store.confirmDialog.isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-[0.97]"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-[0.97]"
        class="pv-record-dialog-panel pv-confirm-dialog-panel"
        :style="$store.confirmDialog.panelStyle()"
        role="dialog"
        aria-modal="true"
        :aria-label="$store.confirmDialog.title || '{{ __('Confirm') }}'"
        @click.stop
    >
        <div
            class="pv-record-dialog-header cursor-move"
            @mousedown="$store.confirmDialog.startDrag($event)"
        >
            <span class="pv-record-dialog-title" x-text="$store.confirmDialog.title || '{{ __('Confirm') }}'"></span>
            <button
                type="button"
                class="pv-record-dialog-close"
                @click="$store.confirmDialog.dismiss()"
                aria-label="{{ __('Close') }}"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="pv-record-dialog-body p-4">
            <p class="whitespace-pre-wrap text-sm text-body" x-text="$store.confirmDialog.message"></p>
            <div
                x-show="$store.confirmDialog.mode === 'prompt' || $store.confirmDialog.promptExpected"
                x-cloak
                class="mt-3"
            >
                <label class="block text-sm">
                    <span
                        class="font-medium text-heading"
                        x-show="$store.confirmDialog.promptExpected"
                        x-text="'{{ __('Confirmation') }}'"
                    ></span>
                    <input
                        type="text"
                        class="mt-1 w-full rounded-md border border-default bg-neutral-primary px-3 py-2 text-sm text-body"
                        x-model="$store.confirmDialog.promptValue"
                        @keydown.enter.prevent="$store.confirmDialog.canAccept() && $store.confirmDialog.accept()"
                    />
                </label>
            </div>
            <div class="mt-4 flex justify-end gap-2 border-t border-default pt-3">
                <button
                    type="button"
                    x-show="$store.confirmDialog.mode === 'confirm' || $store.confirmDialog.mode === 'prompt'"
                    class="pv-btn pv-btn-secondary"
                    @click="$store.confirmDialog.cancel()"
                    x-text="$store.confirmDialog.cancelLabel"
                ></button>
                <button
                    type="button"
                    x-show="$store.confirmDialog.mode === 'confirm' || $store.confirmDialog.mode === 'prompt'"
                    class="pv-btn"
                    :class="$store.confirmDialog.confirmButtonClass()"
                    :disabled="!$store.confirmDialog.canAccept()"
                    @click="$store.confirmDialog.accept()"
                    x-text="$store.confirmDialog.confirmLabel"
                ></button>
                <button
                    type="button"
                    x-show="$store.confirmDialog.mode === 'alert'"
                    class="pv-btn"
                    :class="$store.confirmDialog.confirmButtonClass()"
                    @click="$store.confirmDialog.acknowledge()"
                    x-text="$store.confirmDialog.confirmLabel"
                ></button>
            </div>
        </div>
    </div>
</div>
