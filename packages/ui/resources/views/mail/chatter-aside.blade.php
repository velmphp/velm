<aside class="pv-mail-chatter" aria-label="{{ __('Chatter') }}">
    <div class="flex items-center justify-between gap-2 border-b border-default pb-3">
        <h2 class="text-sm font-semibold text-heading">{{ __('Chatter') }}</h2>
        <template x-if="ctx && !ctx.readonly">
            <button
                type="button"
                class="text-xs font-medium"
                :class="ctx.following ? 'text-fg-brand hover:underline' : 'text-body-subtle hover:text-fg-brand hover:underline'"
                @click="toggleFollow()"
                x-text="ctx.following ? @js(__('Following')) : @js(__('Follow'))"
            ></button>
        </template>
    </div>

    <template x-if="!loading && ctx">
        <div class="mt-4 space-y-4">
            <p class="text-xs text-body-subtle" x-show="ctx.follower_count > 0">
                <span x-text="ctx.follower_count"></span>
                <span x-text="ctx.follower_count === 1 ? @js(__('follower')) : @js(__('followers'))"></span>
            </p>

            <template x-if="ctx.can_post">
                <div class="space-y-2">
                    <label class="sr-only" for="pv-mail-chatter-draft">{{ __('Log a note') }}</label>
                    <textarea
                        id="pv-mail-chatter-draft"
                        class="pv-input w-full min-h-[4.5rem] resize-y text-sm"
                        rows="3"
                        :placeholder="@js(__('Log a note…'))"
                        x-model="draft"
                        @keydown.ctrl.enter.prevent="postMessage()"
                    ></textarea>
                    <button
                        type="button"
                        class="pv-btn pv-btn-primary pv-btn-sm w-full justify-center"
                        :disabled="posting || !(draft || '').trim()"
                        @click="postMessage()"
                    >
                        <span x-show="!posting">{{ __('Post') }}</span>
                        <span x-show="posting">{{ __('Posting…') }}</span>
                    </button>
                </div>
            </template>

            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-body-subtle">{{ __('Messages') }}</p>
                <template x-if="!ctx.messages || !ctx.messages.length">
                    <p class="text-sm text-body-subtle">{{ __('No messages yet.') }}</p>
                </template>
                <ul class="max-h-[min(24rem,50vh)] space-y-3 overflow-y-auto pr-1" x-show="ctx.messages && ctx.messages.length">
                    <template x-for="msg in ctx.messages" :key="msg.id">
                        <li class="rounded-lg border border-default bg-neutral-secondary-soft px-3 py-2">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-xs font-semibold text-heading" x-text="msg.author_name"></span>
                                <span class="shrink-0 text-[0.65rem] text-body-subtle" x-text="msg.date_display"></span>
                            </div>
                            <div
                                class="pv-mail-chatter__body mt-1 text-sm text-body prose prose-sm max-w-none dark:prose-invert"
                                x-html="msg.body_html"
                            ></div>
                        </li>
                    </template>
                </ul>
            </div>

            <template x-if="error">
                <p class="text-sm text-fg-danger" x-text="error"></p>
            </template>
        </div>
    </template>

    <template x-if="loading">
        <p class="mt-4 text-sm text-body-subtle">{{ __('Loading chatter…') }}</p>
    </template>
</aside>
