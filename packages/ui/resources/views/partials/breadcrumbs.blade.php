<nav
    aria-label="{{ __('Breadcrumb') }}"
    class="mb-2 min-w-0"
    x-data="velmBreadcrumbs()"
    x-show="items.length > 1"
    x-cloak
>
    <ol class="flex min-w-0 flex-wrap items-center gap-1.5 text-xs text-body-subtle">
        <template x-for="(item, index) in items" :key="index + '-' + item.label">
            <li class="inline-flex min-w-0 max-w-full items-center gap-1.5">
                <svg
                    x-show="index > 0"
                    aria-hidden="true"
                    class="h-3 w-3 shrink-0 text-body-subtle/50"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
                <a
                    x-show="index < items.length - 1"
                    href="#"
                    class="max-w-[140px] truncate transition-colors hover:text-fg-brand hover:underline"
                    :title="item.label"
                    x-text="item.label"
                    @click.prevent="go(index)"
                ></a>
                <span
                    x-show="index === items.length - 1"
                    class="max-w-[200px] truncate font-medium text-body"
                    aria-current="page"
                    :title="item.label"
                    x-text="item.label"
                ></span>
            </li>
        </template>
    </ol>
</nav>
