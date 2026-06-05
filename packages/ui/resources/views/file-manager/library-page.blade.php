<div data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}">
    @include('velm-ui::partials.breadcrumbs')

    @push('before-livewire')
        @include('velm-ui::file-manager.scripts')
        <script src="{{ \Velm\Ui\UiAssets::fileLibraryScriptHref() }}"></script>
    @endpush

    <div
        class="mb-5 flex flex-col gap-3 border-b border-default/60 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-lg font-semibold tracking-tight text-heading shrink-0">
            {{ __('File library') }}
        </h1>

        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
            <form method="get" action="{{ url('/web/files/library') }}"
                class="flex min-w-0 flex-1 items-center sm:flex-initial sm:min-w-[14rem]">
                @if ($libraryConfig['activeFolderId'] ?? null)
                    <input type="hidden" name="folder_id" value="{{ $libraryConfig['activeFolderId'] }}">
                @endif
                <input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('Search all files…') }}"
                    class="w-full text-sm rounded-md border border-default bg-neutral-primary text-body px-3 py-2 focus:ring-2 focus:ring-fg-brand/30 focus:border-fg-brand">
            </form>
            @if ($libraryConfig['canWrite'] ?? false)
                <button type="button"
                    onclick="(window.pvOpenFileLibraryUpload || function(){document.dispatchEvent(new CustomEvent('pv:files:upload-request'))})()"
                    class="inline-flex shrink-0 items-center gap-1.5 px-3 py-2 text-sm font-medium text-white rounded-md bg-fg-brand hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12M12 7.5v9" />
                    </svg>
                    {{ __('Upload') }}
                </button>
            @endif
        </div>
    </div>

    @include('velm-ui::file-manager.library-inner', ['libraryConfig' => $libraryConfig])
</div>