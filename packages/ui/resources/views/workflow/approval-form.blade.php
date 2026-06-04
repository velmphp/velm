<form class="space-y-4" data-pv-workflow-approval-form @submit.prevent="submitApproval($event)">
    <h3 class="text-base font-semibold text-heading" x-text="title"></h3>
    <label class="block text-sm">
        <span class="font-medium text-heading">{{ __('Comment') }}</span>
        <textarea name="comment" rows="3" class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm" x-model="comment"></textarea>
    </label>
    <p class="text-sm text-fg-danger" x-show="error" x-text="error"></p>
    <div class="flex justify-end gap-2 border-t border-default pt-3">
        <button type="button" class="pv-btn pv-btn-secondary" @click="window.PvWorkflowDialog?.close()">{{ __('Cancel') }}</button>
        <button type="submit" class="pv-btn pv-btn-primary" x-text="submitLabel"></button>
    </div>
</form>
