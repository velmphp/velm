<aside class="pv-workflow-aside">
    <div class="flex items-center justify-between gap-2 border-b border-default pb-3">
        <h2 class="text-sm font-semibold text-heading">{{ __('Workflow') }}</h2>
        <a href="/web/workflow/inbox" class="text-xs font-medium text-fg-brand hover:underline">{{ __('My approvals') }}</a>
    </div>

    <template x-if="!loading && ctx && ctx.has_workflow">
        <div class="mt-4 space-y-5">
            <template x-if="ctx.started">
                <p class="text-sm text-body">
                    <span class="text-body-subtle">{{ __('Current state:') }}</span>
                    <span class="font-medium text-heading" x-text="ctx.state_label || ctx.state"></span>
                    <template x-if="ctx.pending_transition">
                        <span class="text-fg-warning"> — {{ __('awaiting approval') }}</span>
                    </template>
                </p>
            </template>

            <template x-if="!ctx.started && ctx.can_start">
                <button type="button" class="pv-btn pv-btn-primary w-full" @click="start()">{{ __('Start workflow') }}</button>
            </template>

            <template x-if="!ctx.readonly && ctx.transitions && ctx.transitions.length">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-body-subtle">{{ __('Actions') }}</p>
                    <div class="flex flex-col gap-2">
                        <template x-for="tr in ctx.transitions" :key="tr.key">
                            <button
                                type="button"
                                class="pv-btn pv-btn-sm w-full justify-center"
                                :class="tr.kind === 'approval' ? 'pv-btn-secondary' : 'pv-btn-secondary'"
                                @click="runTransition(tr)"
                                x-text="tr.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="ctx.pending_approvals && ctx.pending_approvals.length">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-body-subtle">{{ __('Your pending approvals') }}</p>
                    <template x-for="appr in ctx.pending_approvals" :key="appr.id">
                        <div class="space-y-2 rounded-lg border border-default bg-neutral-secondary-soft px-3 py-2">
                            <span class="block text-sm text-heading" x-text="appr.transition_label"></span>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="pv-btn pv-btn-sm pv-btn-primary flex-1" @click="act(appr.id, true, appr.transition_label)">{{ __('Approve') }}</button>
                                <button type="button" class="pv-btn pv-btn-sm pv-btn-secondary flex-1" @click="act(appr.id, false, appr.transition_label)">{{ __('Reject') }}</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="ctx.timeline && ctx.timeline.length">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-body-subtle">{{ __('History') }}</p>
                    <ul class="space-y-2 border-t border-default pt-3">
                        <template x-for="ev in ctx.timeline" :key="ev.id">
                            <li class="text-xs" :class="ev.pending ? 'text-fg-warning' : 'text-body-subtle'">
                                <span class="font-medium text-heading" x-text="ev.title"></span>
                                <span x-show="ev.at_display" x-text="' · ' + ev.at_display"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <template x-if="error">
                <p class="text-sm text-fg-danger" x-text="error"></p>
            </template>
        </div>
    </template>
</aside>
