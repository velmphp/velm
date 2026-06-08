<div
    x-show="$store.actionBusy.isOpen"
    x-cloak
    class="pv-action-busy-root"
    :class="{ 'pv-action-busy-root--open': $store.actionBusy.isOpen }"
    aria-hidden="true"
>
    <div class="pv-action-busy-backdrop"></div>
    <div class="pv-action-busy-panel" role="status" aria-live="polite" :aria-busy="$store.actionBusy.isOpen ? 'true' : 'false'">
        <div class="pv-action-busy-spinner" aria-hidden="true"></div>
        <p class="pv-action-busy-message" x-text="$store.actionBusy.message || @js(__('Working…'))"></p>
    </div>
</div>
