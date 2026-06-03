{{-- File picker dialog body (PyVelm parity). Loaded via PvDialog or standalone with scripts. --}}
<div class="pv-file-picker"
     data-pv-cfg='@json($pickerConfig)'
     x-data="pvFilePicker(JSON.parse($el.dataset.pvCfg))">

    <div class="flex flex-wrap items-center gap-2 mb-2">
        <input type="search"
               placeholder="{{ __('Search all files…') }}"
               x-model="query"
               @input.debounce.300ms="runSearch()"
               class="flex-1 min-w-[14rem] text-sm rounded-md border border-default
                      bg-neutral-primary text-body px-3 py-1.5
                      focus:ring-2 focus:ring-fg-brand/30 focus:border-fg-brand">
        @if ($canUpload)
        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
                      text-fg-brand border border-fg-brand/30 rounded-md
                      hover:bg-brand-softer cursor-pointer transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12M12 7.5v9"/>
            </svg>
            <span x-text="uploading ? '{{ __('Uploading…') }}' : '{{ __('Upload') }}'"></span>
            <input type="file"
                   multiple
                   class="sr-only"
                   @if ($accept) accept="{{ $accept }}" @endif
                   @change="onPickFiles($event)">
        </label>
        @endif
    </div>

    <nav x-show="!searching"
         class="flex items-center flex-wrap gap-1 text-xs text-body-subtle mb-3 pb-2 border-b border-default">
        <button type="button" @click="navigate(null)"
                class="hover:text-fg-brand hover:underline">{{ __('All files') }}</button>
        <template x-for="(crumb, i) in breadcrumb" :key="crumb.id">
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-body-subtle/50" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
                <button type="button"
                        @click="navigate(crumb.id)"
                        :class="i === breadcrumb.length - 1 ? 'text-body font-medium' : 'hover:text-fg-brand hover:underline'"
                        x-text="crumb.name"></button>
            </span>
        </template>
        <span x-show="folderId" class="ml-auto">
            <button type="button" @click="goUp()"
                    class="inline-flex items-center gap-1 hover:text-fg-brand">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3 3m-3-3v6m6-12H6a2 2 0 00-2 2v0"/>
                </svg>
                {{ __('Up') }}
            </button>
        </span>
    </nav>

    @if ($accept)
    <p class="text-xs text-body-subtle -mt-1 mb-2">
        {{ __('Accepting:') }} <span class="font-mono">{{ $accept }}</span>
    </p>
    @endif

    <div class="max-h-[24rem] overflow-auto space-y-4">
        <div x-show="!searching && folders.length">
            <p class="text-2xs font-semibold uppercase tracking-wider text-body-subtle mb-2">
                {{ __('Folders') }}
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <template x-for="f in folders" :key="'f' + f.id">
                    <button type="button"
                            @click="navigate(f.id)"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg border border-default
                                   bg-neutral-secondary/40 hover:border-fg-brand/40 hover:bg-neutral-secondary
                                   text-left transition">
                        <svg class="w-6 h-6 text-fg-brand/70 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3.75 6A2.25 2.25 0 016 3.75h3.379a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.34a2.25 2.25 0 00-.66-.09H4.41a2.25 2.25 0 00-.66.09V6z" opacity="0.5"/>
                            <path d="M2.4 13.06A2.25 2.25 0 014.64 10.5h14.72a2.25 2.25 0 012.24 2.56l-.7 6A2.25 2.25 0 0118.66 21H5.34a2.25 2.25 0 01-2.24-1.94l-.7-6z"/>
                        </svg>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-medium text-heading truncate" x-text="f.name"></span>
                            <span class="block text-2xs text-body-subtle"
                                  x-text="(f.file_count || 0) + ' {{ __('file(s)') }}'"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div>
            <p x-show="!searching && folders.length"
               class="text-2xs font-semibold uppercase tracking-wider text-body-subtle mb-2">
                {{ __('Files') }}
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-[repeat(auto-fill,minmax(8rem,1fr))] gap-3">
                <template x-for="row in rows" :key="row.id">
                    <button type="button"
                            @click="onTileClick(row)"
                            :class="isSelected(row.id)
                                ? 'border-fg-brand bg-brand-softer ring-2 ring-fg-brand'
                                : 'border-default hover:border-fg-brand/50 hover:bg-neutral-secondary'"
                            class="group relative rounded-lg border bg-neutral-primary
                                   p-2 text-left transition cursor-pointer">
                        <div class="aspect-square mb-2 rounded-md bg-neutral-secondary
                                    overflow-hidden flex items-center justify-center">
                            <template x-if="row.thumbnail_url">
                                <img :src="row.thumbnail_url" alt="" loading="lazy"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'">
                            </template>
                            <template x-if="!row.thumbnail_url">
                                <span x-html="window.pvFileIcon(row.icon, 'w-10 h-10')"></span>
                            </template>
                        </div>
                        <div class="text-xs font-medium text-heading truncate" x-text="row.name"></div>
                        <div class="text-2xs text-body-subtle"
                             x-text="row.size ? humanSize(row.size) : ''"></div>
                    </button>
                </template>
                <template x-if="!rows.length && !folders.length">
                    <div class="col-span-full text-center text-sm text-body-subtle py-10">
                        <span x-show="searching">{{ __('No files match your search.') }}</span>
                        <span x-show="!searching">
                            {{ __('This folder is empty.') }}
                            @if ($canUpload)
                                {{ __('Use Upload to add a file.') }}
                            @endif
                        </span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @if ($multi ?? ($pickerConfig['multi'] ?? false))
    <div class="flex items-center justify-between gap-3 mt-3 pt-3 border-t border-default">
        <span class="text-xs text-body-subtle">
            <span x-text="selected.length"></span> {{ __('selected') }}
        </span>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="window.PvDialog && window.PvDialog.close(null)"
                    class="px-3 py-1.5 text-sm border border-default text-body
                           rounded-md hover:bg-neutral-secondary transition">
                {{ __('Cancel') }}
            </button>
            <button type="button"
                    @click="confirmMulti()"
                    :disabled="!selected.length"
                    class="px-3 py-1.5 text-sm font-medium text-white bg-fg-brand
                           rounded-md hover:opacity-90 transition
                           disabled:opacity-50 disabled:cursor-not-allowed">
                {{ __('Use selected') }}
            </button>
        </div>
    </div>
    @endif

    <p x-show="error" x-text="error" x-cloak
       class="mt-3 text-sm text-fg-danger"></p>
</div>
