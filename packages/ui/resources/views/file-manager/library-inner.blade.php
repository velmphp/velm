<div class="pv-file-library w-full grid gap-4 items-start content-start grid-cols-1"
     data-pv-library-cfg='@json($libraryConfig)'
     :class="detailsId !== null
         ? 'lg:grid-cols-[15rem_minmax(0,1fr)_22rem]'
         : 'lg:grid-cols-[15rem_minmax(0,1fr)]'"
     x-data="pvFileLibrary(JSON.parse($el.dataset.pvLibraryCfg))"
     @click="contextMenu = null; folderContextMenu = null; actionMenu = null">

    {{-- ───── Left: folder tree ───── --}}
    <aside class="pv-file-library__folders w-full self-start h-fit
                  bg-neutral-primary border border-default rounded-lg lg:sticky lg:top-20
                  lg:max-h-[calc(100vh-6rem)] overflow-y-auto flex flex-col">
        <header class="flex items-center justify-between gap-2 px-3 h-11 border-b border-default shrink-0">
            <span class="text-xs font-semibold uppercase tracking-wider text-body-subtle">Folders</span>
            <button type="button" @click="startNewFolder(newFolderParentForHeader())"
                    title="New folder" class="text-body-subtle hover:text-fg-brand">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
            </button>
        </header>

        <ul class="space-y-0.5 text-sm p-2 flex-1 overflow-y-auto">
            <li>
                <a href="/web/files/library" @click.prevent="goFolder(null)"
                   @dragover.prevent @drop.prevent="dropOnFolder(null, $event)"
                   :class="activeFolderId === null ? 'bg-brand-soft text-fg-brand-strong font-medium' : 'text-body hover:bg-neutral-secondary hover:text-heading'"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md transition">
                    <svg class="w-4 h-4 text-body-subtle shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                    </svg>
                    <span class="flex-1 truncate">All files</span>
                </a>
            </li>
            <li>
                <a href="/web/files/library?folder_id=0" @click.prevent="goFolder(0)"
                   @dragover.prevent @drop.prevent="dropOnFolder(null, $event)"
                   :class="activeFolderId === 0 ? 'bg-brand-soft text-fg-brand-strong font-medium' : 'text-body hover:bg-neutral-secondary hover:text-heading'"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md transition">
                    <svg class="w-4 h-4 text-body-subtle shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="flex-1 truncate">Unfiled</span>
                    <span class="text-2xs text-body-subtle" x-text="unfiledCount"></span>
                </a>
            </li>
            <li class="border-t border-default my-2"></li>
            <template x-for="node in renderableFolders()" :key="node.id">
                <li class="flex items-center" :style="folderIndentStyle(node)">
                    {{-- Collapse toggle — only when the node has children. --}}
                    <button type="button" class="w-4 h-4 shrink-0 flex items-center justify-center text-body-subtle hover:text-heading"
                            x-show="node.hasChildren" @click.stop="toggleCollapse(node.id)"
                            :title="isCollapsed(node.id) ? 'Expand' : 'Collapse'">
                        <svg class="w-3 h-3 transition-transform" :class="isCollapsed(node.id) ? '' : 'rotate-90'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                    <span class="w-4 shrink-0" x-show="!node.hasChildren"></span>
                    <a :href="'/web/files/library?folder_id=' + node.id" @click.prevent="goFolder(node.id)"
                       @dragover.prevent @drop.prevent="dropOnFolder(node.id, $event)"
                       @contextmenu.prevent="openFolderMenu(node, $event)"
                       :class="activeFolderId === node.id ? 'bg-brand-soft text-fg-brand-strong font-medium' : 'text-body hover:bg-neutral-secondary hover:text-heading'"
                       class="flex-1 min-w-0 flex items-center gap-2 px-2 py-1.5 rounded-md transition">
                        <svg class="w-4 h-4 text-fg-brand/70 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3.75 6A2.25 2.25 0 016 3.75h3.379a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.34a2.25 2.25 0 00-.66-.09H4.41a2.25 2.25 0 00-.66.09V6z" opacity="0.5"/>
                            <path d="M2.4 13.06A2.25 2.25 0 014.64 10.5h14.72a2.25 2.25 0 012.24 2.56l-.7 6A2.25 2.25 0 0118.66 21H5.34a2.25 2.25 0 01-2.24-1.94l-.7-6z"/>
                        </svg>
                        <span class="flex-1 truncate" x-text="node.name"></span>
                        <span class="text-2xs text-body-subtle" x-text="node.file_count"></span>
                    </a>
                </li>
            </template>
        </ul>
    </aside>

    {{-- ───── Center: header band + file area ───── --}}
    <section class="w-full min-w-0 self-start bg-neutral-primary border border-default rounded-lg flex flex-col">
        <header class="flex items-center gap-2 px-3 h-11 border-b border-default shrink-0">
            <template x-if="selected.size === 0">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <nav class="flex items-center gap-1 min-w-0 flex-1 text-xs text-body-subtle overflow-hidden">
                        <button type="button" @click="goFolder(null)"
                                :class="activeFolderId === null ? 'text-body font-medium' : 'hover:text-fg-brand hover:underline'"
                                class="shrink-0">All files</button>
                        <template x-if="activeFolderId === 0">
                            <span class="flex items-center gap-1 shrink-0">
                                <svg class="w-3 h-3 text-body-subtle/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                <span class="text-body font-medium">Unfiled</span>
                            </span>
                        </template>
                        <template x-for="(crumb, i) in breadcrumb()" :key="crumb.id">
                            <span class="flex items-center gap-1 min-w-0">
                                <svg class="w-3 h-3 text-body-subtle/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                <button type="button" @click="goFolder(crumb.id)"
                                        :class="i === breadcrumb().length - 1 ? 'text-body font-medium' : 'hover:text-fg-brand hover:underline'"
                                        class="truncate" x-text="crumb.name"></button>
                            </span>
                        </template>
                        <span x-show="searching" class="text-body font-medium shrink-0">Search results</span>
                    </nav>
                    <button type="button" x-show="activeFolderId && activeFolderId > 0" @click="goUp()"
                            title="Up one level"
                            class="inline-flex h-7 items-center gap-1.5 px-2.5 rounded-md text-xs border border-default text-body hover:bg-neutral-secondary transition shrink-0">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3 3m-3-3v6m6-12H6a2 2 0 00-2 2v0"/></svg>
                        Up
                    </button>
                    {{-- View switcher --}}
                    <div class="inline-flex h-7 items-stretch rounded-md border border-default overflow-hidden shrink-0">
                        <button type="button" @click="setView('grid')" title="Grid"
                                :class="view === 'grid' ? 'bg-brand-soft text-fg-brand-strong' : 'text-body-subtle hover:bg-neutral-secondary'"
                                class="inline-flex h-7 w-7 items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                        </button>
                        <button type="button" @click="setView('tiles')" title="Tiles"
                                :class="view === 'tiles' ? 'bg-brand-soft text-fg-brand-strong' : 'text-body-subtle hover:bg-neutral-secondary'"
                                class="inline-flex h-7 w-7 items-center justify-center border-l border-default">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        <button type="button" @click="setView('details')" title="Details"
                                :class="view === 'details' ? 'bg-brand-soft text-fg-brand-strong' : 'text-body-subtle hover:bg-neutral-secondary'"
                                class="inline-flex h-7 w-7 items-center justify-center border-l border-default">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z"/></svg>
                        </button>
                    </div>
                    <button type="button" x-show="canWrite" @click="startNewFolder(newFolderParentForHeader())"
                            title="New folder"
                            class="inline-flex h-7 items-center gap-1.5 px-2.5 rounded-md text-xs font-medium
                                   border border-default text-body hover:bg-neutral-secondary hover:text-heading transition shrink-0">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m-3.75-6h13.5A2.25 2.25 0 0121 9.75v8.25a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V6a2.25 2.25 0 012.25-2.25h3.879a1.5 1.5 0 011.06.44l1.122 1.12a1.5 1.5 0 001.06.44H18.75"/>
                        </svg>
                        New folder
                    </button>
                    <span class="text-2xs text-body-subtle shrink-0" x-text="files.length + ' item(s)'"></span>
                </div>
            </template>
            <template x-if="selected.size > 0">
                <div class="flex items-center gap-2 flex-1 relative">
                    <span class="text-xs font-semibold text-fg-brand-strong"><span x-text="selected.size"></span> selected</span>
                    <div class="flex-1"></div>
                    <button type="button" @click="downloadSelected()" class="px-2 py-1 rounded text-xs border border-default bg-neutral-primary text-body hover:bg-neutral-secondary transition">Download</button>
                    <div class="relative" @click.stop>
                        <button type="button" @click="openActionMenu(actionMenu === 'move' ? null : 'move')"
                                class="px-2 py-1 rounded text-xs border border-default bg-neutral-primary text-body hover:bg-neutral-secondary transition">Move to ▾</button>
                    </div>
                    <div class="relative" @click.stop>
                        <button type="button" @click="openActionMenu(actionMenu === 'copy' ? null : 'copy')"
                                class="px-2 py-1 rounded text-xs border border-default bg-neutral-primary text-body hover:bg-neutral-secondary transition">Copy to ▾</button>
                    </div>
                    <button type="button" @click="togglePublicSelected()" class="px-2 py-1 rounded text-xs border border-default bg-neutral-primary text-body hover:bg-neutral-secondary transition">Public</button>
                    <button type="button" @click="deleteSelected()" class="px-2 py-1 rounded text-xs border border-fg-danger/30 text-fg-danger hover:bg-danger-soft transition">Delete</button>
                    <button type="button" @click="clearSelection()" title="Clear selection" class="text-body-subtle hover:text-heading">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    {{-- Move/Copy target chooser --}}
                    <div x-show="actionMenu" x-cloak @click.stop
                         class="absolute right-0 top-full mt-1 z-50 w-56 max-h-72 overflow-y-auto
                                rounded-lg border border-default bg-neutral-primary shadow-lg py-1 text-sm">
                        <p class="px-3 py-1.5 text-2xs uppercase tracking-wider text-body-subtle"
                           x-text="(actionMenu === 'copy' ? 'Copy' : 'Move') + ' to…'"></p>
                        <button type="button" @click="chooseActionTarget(null)"
                                class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary">Unfiled (no folder)</button>
                        <template x-for="node in renderableFolders()" :key="'t' + node.id">
                            <button type="button" @click="chooseActionTarget(node.id)"
                                    :style="folderIndentStyle(node)"
                                    class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary truncate"
                                    x-text="node.name"></button>
                        </template>
                    </div>
                </div>
            </template>
        </header>

        <div class="p-3 space-y-4">
            {{-- Child folders — navigable tiles. --}}
            <template x-if="childFolders().length || (canWrite && !searching)">
                <div>
                    <p class="text-2xs font-semibold uppercase tracking-wider text-body-subtle mb-2">Folders</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        {{-- Add-folder tile — creates a subfolder of the current directory. --}}
                        <template x-if="canWrite && !searching">
                            <button type="button" @click="startNewFolder(newFolderParentForHeader())"
                                    class="flex items-center gap-2 px-3 py-2.5 rounded-lg border border-dashed border-default
                                           text-fg-brand hover:border-fg-brand hover:bg-brand-softer text-left transition">
                                <svg class="w-7 h-7 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m-3.75-6h13.5A2.25 2.25 0 0121 9.75v8.25a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V6a2.25 2.25 0 012.25-2.25h3.879a1.5 1.5 0 011.06.44l1.122 1.12a1.5 1.5 0 001.06.44H18.75"/>
                                </svg>
                                <span class="text-sm font-medium">New folder</span>
                            </button>
                        </template>
                        <template x-for="f in childFolders()" :key="'cf' + f.id">
                            <button type="button" @click="goFolder(f.id)"
                                    @contextmenu.prevent="openFolderMenu(f, $event)"
                                    @dragover.prevent @drop.prevent="dropOnFolder(f.id, $event)"
                                    class="flex items-center gap-2 px-3 py-2.5 rounded-lg border border-default
                                           bg-neutral-secondary/40 hover:border-fg-brand/40 hover:bg-neutral-secondary text-left transition">
                                <svg class="w-7 h-7 text-fg-brand/70 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3.75 6A2.25 2.25 0 016 3.75h3.379a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.34a2.25 2.25 0 00-.66-.09H4.41a2.25 2.25 0 00-.66.09V6z" opacity="0.5"/>
                                    <path d="M2.4 13.06A2.25 2.25 0 014.64 10.5h14.72a2.25 2.25 0 012.24 2.56l-.7 6A2.25 2.25 0 0118.66 21H5.34a2.25 2.25 0 01-2.24-1.94l-.7-6z"/>
                                </svg>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-heading truncate" x-text="f.name"></span>
                                    <span class="block text-2xs text-body-subtle" x-text="((f.child_count||0)+' folder(s), '+(f.file_count||0)+' file(s)')"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Files — rendered in the chosen view mode. --}}
            <div>
                <p x-show="childFolders().length || (canWrite && !searching)" class="text-2xs font-semibold uppercase tracking-wider text-body-subtle mb-2">Files</p>

                {{-- GRID (tight thumbnails) --}}
                <div x-show="view === 'grid'"
                     class="grid grid-cols-1 sm:grid-cols-[repeat(auto-fill,minmax(6.5rem,1fr))] gap-2">
                    <template x-for="file in files" :key="file.id">
                        <button type="button" draggable="true"
                                @click="onFileClick(file, $event)"
                                @dragstart="onFileDragStart(file, $event)"
                                @contextmenu="onFileContext(file, $event)"
                                :class="isSelected(file.id) ? 'border-fg-brand ring-2 ring-fg-brand bg-brand-softer' : 'border-default hover:border-fg-brand/50 hover:bg-neutral-secondary'"
                                class="rounded-lg border bg-neutral-primary p-1.5 text-left transition">
                            <div class="aspect-square mb-1 rounded bg-neutral-secondary overflow-hidden flex items-center justify-center">
                                <template x-if="file.thumbnail_url"><img :src="file.thumbnail_url" alt="" loading="lazy" class="w-full h-full object-cover" onerror="this.style.display='none'"></template>
                                <template x-if="!file.thumbnail_url"><span x-html="fileIcon(file, 'w-9 h-9')"></span></template>
                            </div>
                            <div class="text-2xs font-medium text-heading truncate" x-text="file.name"></div>
                        </button>
                    </template>
                </div>

                {{-- TILES (medium, icon + name + meta) --}}
                <div x-show="view === 'tiles'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2">
                    <template x-for="file in files" :key="'t' + file.id">
                        <button type="button" draggable="true"
                                @click="onFileClick(file, $event)"
                                @dragstart="onFileDragStart(file, $event)"
                                @contextmenu="onFileContext(file, $event)"
                                :class="isSelected(file.id) ? 'border-fg-brand ring-2 ring-fg-brand bg-brand-softer' : 'border-default hover:border-fg-brand/50 hover:bg-neutral-secondary'"
                                class="flex items-center gap-3 rounded-lg border bg-neutral-primary p-2 text-left transition">
                            <div class="w-12 h-12 rounded bg-neutral-secondary overflow-hidden flex items-center justify-center shrink-0">
                                <template x-if="file.thumbnail_url"><img :src="file.thumbnail_url" alt="" loading="lazy" class="w-full h-full object-cover" onerror="this.style.display='none'"></template>
                                <template x-if="!file.thumbnail_url"><span x-html="fileIcon(file, 'w-7 h-7')"></span></template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-heading truncate" x-text="file.name"></div>
                                <div class="text-2xs text-body-subtle truncate"
                                     x-text="(file.mimetype || '') + (file.size ? ' · ' + humanSize(file.size) : '')"></div>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- DETAILS (table) --}}
                <div x-show="view === 'details'" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-2xs uppercase tracking-wider text-body-subtle border-b border-default">
                                <th class="py-1.5 px-2 font-semibold">Name</th>
                                <th class="py-1.5 px-2 font-semibold hidden sm:table-cell">Type</th>
                                <th class="py-1.5 px-2 font-semibold text-right">Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="file in files" :key="'d' + file.id">
                                <tr draggable="true"
                                    @click="onFileClick(file, $event)"
                                    @dragstart="onFileDragStart(file, $event)"
                                    @contextmenu="onFileContext(file, $event)"
                                    :class="isSelected(file.id) ? 'bg-brand-softer' : 'hover:bg-neutral-secondary'"
                                    class="border-b border-default/60 cursor-pointer transition">
                                    <td class="py-1.5 px-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="w-5 h-5 shrink-0 flex items-center justify-center">
                                                <template x-if="file.thumbnail_url"><img :src="file.thumbnail_url" alt="" class="w-5 h-5 rounded object-cover" onerror="this.style.display='none'"></template>
                                                <template x-if="!file.thumbnail_url"><span x-html="fileIcon(file, 'w-5 h-5')"></span></template>
                                            </span>
                                            <span class="truncate text-heading" x-text="file.name"></span>
                                        </div>
                                    </td>
                                    <td class="py-1.5 px-2 text-body-subtle hidden sm:table-cell truncate" x-text="file.mimetype"></td>
                                    <td class="py-1.5 px-2 text-right text-body-subtle whitespace-nowrap" x-text="humanSize(file.size)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <template x-if="!files.length">
                    <div class="text-center text-sm text-body-subtle py-10">
                        <span x-show="searching">No files match your search.</span>
                        <span x-show="!searching">This folder has no files. Use <strong>Upload</strong> to add some.</span>
                    </div>
                </template>
            </div>
        </div>
    </section>

    {{-- ───── Right: details slide-over ───── --}}
    <aside class="lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] overflow-y-auto self-start
                  bg-neutral-primary border border-default rounded-lg flex flex-col"
           x-show="detailsId !== null" x-cloak>
        <header class="flex items-center justify-between gap-2 px-3 h-11 border-b border-default shrink-0">
            <span class="text-xs font-semibold uppercase tracking-wider text-body-subtle">Details</span>
            <button type="button" @click="detailsId = null" class="text-body-subtle hover:text-heading">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div id="pv-file-details" class="p-3 text-sm text-body-subtle flex-1 overflow-y-auto">
            Click a file to see its details.
        </div>
    </aside>

    {{-- ───── File context menu ───── --}}
    <div x-show="contextMenu" x-cloak @click.stop :style="contextMenuStyle()"
         class="min-w-[12rem] rounded-lg border border-default bg-neutral-primary shadow-lg py-1 text-sm">
        <template x-if="contextMenu">
            <div>
                <button type="button" @click="openDetailsFromMenu()" class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary">Details</button>
                <a :href="'/web/files/' + contextMenu.id + '/properties'" class="block px-3 py-1.5 text-body hover:bg-neutral-secondary">Properties page</a>
                <a :href="'/api/attachment/' + contextMenu.id + '/download'" class="block px-3 py-1.5 text-body hover:bg-neutral-secondary">Download</a>
                <button type="button" @click="togglePublicFromMenu()" class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary">Toggle public</button>
                <div class="border-t border-default my-1"></div>
                <button type="button" @click="deleteFromMenu()" class="block w-full text-left px-3 py-1.5 text-fg-danger hover:bg-danger-soft">Delete</button>
            </div>
        </template>
    </div>

    {{-- ───── Folder context menu ───── --}}
    <div x-show="folderContextMenu" x-cloak @click.stop :style="folderContextMenuStyle()"
         class="min-w-[11rem] rounded-lg border border-default bg-neutral-primary shadow-lg py-1 text-sm">
        <template x-if="folderContextMenu">
            <div>
                <button type="button" @click="goFolder(folderContextMenu.id)" class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary">Open</button>
                <button type="button" @click="newSubfolderFromMenu()" class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary">New subfolder</button>
                <button type="button" @click="renameFolderFromMenu()" class="block w-full text-left px-3 py-1.5 text-body hover:bg-neutral-secondary">Rename</button>
                <div class="border-t border-default my-1"></div>
                <button type="button" @click="deleteFolderFromMenu()" class="block w-full text-left px-3 py-1.5 text-fg-danger hover:bg-danger-soft">Delete</button>
            </div>
        </template>
    </div>

    {{-- ───── New-folder dialog ───── --}}
    <div x-show="creatingFolder" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         @keydown.escape.window="cancelNewFolder()">
        <div class="absolute inset-0 bg-black/40" @click="cancelNewFolder()"></div>
        <form @submit.prevent="submitNewFolder()"
              class="relative w-full max-w-md bg-neutral-primary border border-default rounded-xl shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-heading">New folder</h2>
                <button type="button" @click="cancelNewFolder()" class="text-body-subtle hover:text-heading">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-body-subtle">
                Inside <span class="font-medium text-body" x-text="newFolderParentLabel()"></span>
            </p>
            <div>
                <label class="block text-sm font-medium text-body mb-1">Folder name</label>
                <input type="text" x-ref="newFolderInput" x-model="newFolderName"
                       placeholder="e.g. Marketing"
                       class="block w-full text-sm rounded-md border border-default bg-neutral-primary
                              text-body px-3 py-2 focus:ring-2 focus:ring-fg-brand/30 focus:border-fg-brand">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-default">
                <button type="button" @click="cancelNewFolder()" class="px-3 py-1.5 text-sm border border-default text-body rounded-md hover:bg-neutral-secondary transition">Cancel</button>
                <button type="submit" :disabled="!newFolderName.trim() || folderBusy"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-fg-brand rounded-md hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-text="folderBusy ? 'Creating…' : 'Create folder'"></span>
                </button>
            </div>
        </form>
    </div>

    {{-- ───── Upload dialog ───── --}}
    <div x-show="uploadOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         @keydown.escape.window="closeUploadDialog()">
        <div class="absolute inset-0 bg-black/40" @click="closeUploadDialog()"></div>
        <div class="relative w-full max-w-md bg-neutral-primary border border-default rounded-xl shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-heading">Upload to <span x-text="uploadTargetLabel()"></span></h2>
                <button type="button" @click="closeUploadDialog()" class="text-body-subtle hover:text-heading">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <input type="file" multiple @change="onUploadPick($event)"
                   class="block w-full text-sm text-body file:mr-3 file:py-2 file:px-4 file:rounded-md
                          file:border-0 file:text-sm file:font-medium file:bg-fg-brand file:text-white
                          hover:file:opacity-90 file:cursor-pointer cursor-pointer">
            <label class="flex items-center gap-2 text-sm text-body cursor-pointer">
                <input type="checkbox" x-model="uploadPublic"
                       class="w-4 h-4 rounded border-default bg-neutral-secondary text-fg-brand checked:bg-fg-brand focus:ring-2 focus:ring-fg-brand">
                <span>Public — serve without an access grant.</span>
            </label>
            <p x-show="uploadError" x-text="uploadError" x-cloak class="text-sm text-fg-danger"></p>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-default">
                <button type="button" @click="closeUploadDialog()" class="px-3 py-1.5 text-sm border border-default text-body rounded-md hover:bg-neutral-secondary transition">Cancel</button>
                <button type="button" @click="submitUploadDialog()" :disabled="!uploadFiles.length || uploadBusy"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-fg-brand rounded-md hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-text="uploadBusy ? 'Uploading…' : 'Upload'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
