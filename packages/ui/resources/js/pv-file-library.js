
(function () {
    const factory = (cfg) => ({
        activeFolderId: cfg.activeFolderId,
        folders: cfg.folders || [],
        unfiledCount: cfg.unfiledCount || 0,
        files: cfg.files || [],
        visibleIds: cfg.visibleIds || [],
        searching: !!cfg.searching,
        canWrite: !!cfg.canWrite,
        view: 'grid',
        collapsed: new Set(),
        selected: new Set(),
        lastClicked: null,
        detailsId: null,
        contextMenu: null,
        folderContextMenu: null,
        actionMenu: null,          // null | 'move' | 'copy'
        creatingFolder: false,
        newFolderParentId: null,
        newFolderName: '',
        folderBusy: false,
        uploadOpen: false,
        uploadFiles: [],
        uploadPublic: false,
        uploadBusy: false,
        uploadError: '',

        init() {
            window.pvOpenFileLibraryUpload = () => this.openUploadDialog();
            document.addEventListener('pv:files:upload-request', () => this.openUploadDialog());
            const saved = window.localStorage
                ? window.localStorage.getItem('pvFileView') : null;
            if (saved === 'grid' || saved === 'tiles' || saved === 'details') {
                this.view = saved;
            }
            this.collapseAllbut(this.activeFolderId);
        },

        // Collapse every branch except the ancestor path leading to `folderId`
        // (the current working directory), so only that path is expanded.
        collapseAllbut(folderId) {
            const keepOpen = new Set();
            let cursor = (folderId && folderId > 0) ? this.folderById(folderId) : null;
            let guard = 0;
            while (cursor && guard++ < 64) {
                keepOpen.add(cursor.id);
                cursor = cursor.parent_id ? this.folderById(cursor.parent_id) : null;
            }
            const next = new Set();
            for (const f of this.folders) {
                if (this.childrenOf(f.id).length && !keepOpen.has(f.id)) next.add(f.id);
            }
            this.collapsed = next;
        },

        // ── view mode ──
        setView(v) {
            this.view = v;
            try { window.localStorage && window.localStorage.setItem('pvFileView', v); } catch (e) {}
        },

        fileIcon(file, sizeClass) {
            return window.pvFileIcon ? window.pvFileIcon(file.icon || 'file', sizeClass) : '';
        },
        humanSize(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        },

        // ── folder tree (with collapse) ──
        folderById(id) { return this.folders.find((x) => x.id === id); },
        childrenOf(id) {
            return this.folders
                .filter((f) => (f.parent_id || null) === id)
                .sort((a, b) => (a.sequence - b.sequence) || a.name.localeCompare(b.name));
        },
        isCollapsed(id) { return this.collapsed.has(id); },
        toggleCollapse(id) {
            if (this.collapsed.has(id)) this.collapsed.delete(id);
            else this.collapsed.add(id);
            this.collapsed = new Set(this.collapsed);
        },
        renderableFolders() {
            const out = [];
            const walk = (parentId, depth) => {
                for (const k of this.childrenOf(parentId)) {
                    const hasChildren = this.childrenOf(k.id).length > 0;
                    out.push({ ...k, depth, hasChildren });
                    if (hasChildren && !this.collapsed.has(k.id)) walk(k.id, depth + 1);
                }
            };
            walk(null, 0);
            return out;
        },
        folderIndentStyle(node) { return { paddingLeft: (node.depth * 14) + 'px' }; },

        // Subfolder tiles in the centre panel for the active folder.
        childFolders() {
            if (this.activeFolderId === 0 || this.searching) return [];
            const target = (this.activeFolderId && this.activeFolderId > 0)
                ? this.activeFolderId : null;
            return this.childrenOf(target);
        },

        goFolder(id) {
            const url = new URL(window.location.href);
            url.searchParams.delete('q');
            if (id === null) url.searchParams.delete('folder_id');
            else url.searchParams.set('folder_id', String(id));
            window.location.href = url.toString();
        },

        breadcrumb() {
            if (!this.activeFolderId || this.activeFolderId === 0) return [];
            const chain = [];
            let cursor = this.folderById(this.activeFolderId);
            let guard = 0;
            while (cursor && guard < 32) {
                chain.unshift({ id: cursor.id, name: cursor.name });
                cursor = cursor.parent_id ? this.folderById(cursor.parent_id) : null;
                guard += 1;
            }
            return chain;
        },
        goUp() {
            const chain = this.breadcrumb();
            if (chain.length >= 2) this.goFolder(chain[chain.length - 2].id);
            else this.goFolder(null);
        },
        activeFolderLabel() {
            if (this.activeFolderId === null) return 'All files';
            if (this.activeFolderId === 0) return 'Unfiled';
            const f = this.folderById(this.activeFolderId);
            return f ? f.name : 'Folder';
        },

        // ── selection ──
        onFileClick(file, ev) {
            const id = file.id;
            if (ev.shiftKey && this.lastClicked !== null) {
                const a = this.visibleIds.indexOf(this.lastClicked);
                const b = this.visibleIds.indexOf(id);
                if (a >= 0 && b >= 0) {
                    const [lo, hi] = a < b ? [a, b] : [b, a];
                    for (let i = lo; i <= hi; i++) this.selected.add(this.visibleIds[i]);
                } else { this.selected.add(id); }
            } else if (ev.ctrlKey || ev.metaKey) {
                if (this.selected.has(id)) this.selected.delete(id);
                else this.selected.add(id);
            } else {
                this.selected = new Set([id]);
            }
            this.lastClicked = id;
            this.openPropertiesPanel(id);
            this.selected = new Set(this.selected);
        },
        onFileContext(file, ev) {
            ev.preventDefault();
            this.folderContextMenu = null;
            this.actionMenu = null;
            if (!this.selected.has(file.id)) this.selected = new Set([file.id]);
            this.contextMenu = { id: file.id, x: ev.clientX, y: ev.clientY };
        },
        isSelected(id) { return this.selected.has(id); },
        clearSelection() { this.selected = new Set(); this.lastClicked = null; },
        selectedArray() { return Array.from(this.selected); },

        onFileDragStart(file, ev) {
            const ids = this.selected.has(file.id) ? Array.from(this.selected) : [file.id];
            ev.dataTransfer.setData('application/x-pyvelm-attachment-ids', JSON.stringify(ids));
            ev.dataTransfer.effectAllowed = 'copyMove';
        },

        contextMenuStyle() {
            if (!this.contextMenu) return {};
            return { position: 'fixed', top: this.contextMenu.y + 'px', left: this.contextMenu.x + 'px', zIndex: 60 };
        },
        folderContextMenuStyle() {
            if (!this.folderContextMenu) return {};
            return { position: 'fixed', top: this.folderContextMenu.y + 'px', left: this.folderContextMenu.x + 'px', zIndex: 60 };
        },

        async openPropertiesPanel(id) {
            this.detailsId = id;
            const target = document.getElementById('pv-file-details');
            if (!target) return;
            try {
                const r = await fetch(`/web/files/${id}/properties_panel`, { credentials: 'same-origin' });
                if (!r.ok) throw new Error(r.statusText);
                target.innerHTML = await r.text();
            } catch (err) {
                target.innerHTML = '<p class="text-fg-danger">Could not load details.</p>';
            }
        },

        closeContextMenu() { this.contextMenu = null; },
        openDetailsFromMenu() { if (this.contextMenu) { this.openPropertiesPanel(this.contextMenu.id); this.closeContextMenu(); } },
        togglePublicFromMenu() { if (this.contextMenu) { this.togglePublicOne(this.contextMenu.id); this.closeContextMenu(); } },
        deleteFromMenu() { if (this.contextMenu) { this.deleteOne(this.contextMenu.id); this.closeContextMenu(); } },

        // ── folder DnD target ──
        async dropOnFolder(folderId, ev) {
            let ids = [];
            const payload = ev.dataTransfer.getData('application/x-pyvelm-attachment-ids');
            if (payload) { try { ids = JSON.parse(payload); } catch (e) { ids = []; } }
            if (!ids.length) return;
            await this._move(ids, folderId);
        },

        // ── new-folder dialog ──
        newFolderParentForHeader() {
            return (this.activeFolderId && this.activeFolderId > 0) ? this.activeFolderId : null;
        },
        newFolderParentLabel() {
            if (!this.newFolderParentId) return 'top level';
            const f = this.folderById(this.newFolderParentId);
            return f ? f.name : 'folder';
        },
        startNewFolder(parentId = null) {
            this.folderContextMenu = null; this.contextMenu = null; this.actionMenu = null;
            this.newFolderParentId = parentId;
            this.creatingFolder = true; this.newFolderName = ''; this.folderBusy = false;
            this.$nextTick(() => this.$refs.newFolderInput && this.$refs.newFolderInput.focus());
        },
        cancelNewFolder() { this.creatingFolder = false; this.newFolderName = ''; this.newFolderParentId = null; this.folderBusy = false; },
        async submitNewFolder() {
            const name = (this.newFolderName || '').trim();
            if (!name || this.folderBusy) return;
            const parent = this.newFolderParentId;
            this.folderBusy = true;
            try {
                const r = await fetch('/web/files/folders', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    body: JSON.stringify({ name, parent_id: parent }),
                });
                if (!r.ok) throw new Error(await r.text() || r.statusText);
                const payload = await r.json();
                const parentId = payload.parent_id || null;
                this.folders.push({ id: payload.id, name: payload.name, parent_id: parentId,
                    sequence: 10, color: '', child_count: 0, file_count: 0 });
                if (parentId) { const p = this.folderById(parentId); if (p) p.child_count = (p.child_count || 0) + 1; }
                // Expand the parent so the freshly-created child is visible.
                if (parentId) { this.collapsed.delete(parentId); this.collapsed = new Set(this.collapsed); }
                this.cancelNewFolder();
                window.pvToast && window.pvToast(`Folder "${payload.name}" created.`);
            } catch (err) {
                this.folderBusy = false;
                window.pvAlert && window.pvAlert('Could not create folder: ' + (err.message || err), { variant: 'danger' });
            }
        },

        // ── folder context menu ──
        openFolderMenu(node, ev) {
            this.contextMenu = null; this.actionMenu = null;
            this.folderContextMenu = { id: node.id, name: node.name, x: ev.clientX, y: ev.clientY };
        },
        closeFolderContextMenu() { this.folderContextMenu = null; },
        newSubfolderFromMenu() { if (this.folderContextMenu) this.startNewFolder(this.folderContextMenu.id); },
        async renameFolderFromMenu() {
            const node = this.folderContextMenu;
            if (!node) return;
            this.closeFolderContextMenu();
            const name = await window.pvPrompt('Rename folder', node.name, { title: 'Rename folder' });
            if (!name || name === node.name) return;
            try {
                const r = await fetch(`/web/files/folders/${node.id}`, {
                    method: 'PATCH', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    body: JSON.stringify({ name }),
                });
                if (!r.ok) throw new Error(r.statusText);
                const row = this.folderById(node.id); if (row) row.name = name;
                window.pvToast && window.pvToast(`Folder renamed to "${name}".`);
            } catch (err) {
                window.pvAlert && window.pvAlert('Rename failed: ' + (err.message || err), { variant: 'danger' });
            }
        },
        async deleteFolderFromMenu() {
            const node = this.folderContextMenu;
            if (!node) return;
            this.closeFolderContextMenu();
            const r = await fetch(`/web/files/folders/${node.id}`, {
                method: 'DELETE', credentials: 'same-origin',
                headers: { 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
            });
            if (r.status === 409) {
                window.pvAlert && window.pvAlert('Folder is not empty — move or delete the files first.', { variant: 'warning' });
                return;
            }
            if (!r.ok) { window.pvAlert && window.pvAlert('Could not delete folder.', { variant: 'danger' }); return; }
            window.location.reload();
        },

        // ── bulk actions ──
        downloadSelected() {
            const ids = Array.from(this.selected);
            if (!ids.length) return;
            if (ids.length === 1) { window.location = '/api/attachment/' + ids[0] + '/download'; return; }
            const csrf = window.pvCsrf ? window.pvCsrf() : '';
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = '/web/files/bulk/download';
            f.style.display = 'none';
            const input = document.createElement('input');
            input.name = 'ids';
            input.value = ids.join(',');
            f.appendChild(input);
            if (csrf) {
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = csrf;
                f.appendChild(token);
            }
            document.body.appendChild(f);
            f.submit();
            document.body.removeChild(f);
        },
        async togglePublicSelected() {
            const ids = Array.from(this.selected);
            if (!ids.length) return;
            try {
                const r = await fetch('/web/files/bulk/public', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    body: JSON.stringify({ ids, public: true }),
                });
                if (!r.ok) throw new Error(r.statusText);
                window.pvToast && window.pvToast(`Updated ${ids.length} file(s) → public.`);
            } catch (err) {
                window.pvAlert && window.pvAlert('Could not update: ' + (err.message || err), { variant: 'danger' });
            }
        },
        async togglePublicOne(id) { return this.togglePublicSelected.call({ ...this, selected: new Set([id]) }); },

        async deleteSelected() {
            const ids = Array.from(this.selected);
            if (!ids.length) return;
            const ok = await window.pvConfirm(`Delete ${ids.length} file(s)?`, { title: 'Delete files', variant: 'danger', confirmLabel: 'Delete' });
            if (!ok) return;
            try {
                const r = await fetch('/web/files/bulk/delete', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    body: JSON.stringify({ ids }),
                });
                if (!r.ok && r.status !== 204) throw new Error(r.statusText);
                window.pvToast && window.pvToast(`Deleted ${ids.length} file(s).`);
                window.location.reload();
            } catch (err) {
                window.pvAlert && window.pvAlert('Delete failed: ' + (err.message || err), { variant: 'danger' });
            }
        },
        async deleteOne(id) {
            const ok = await window.pvConfirm('Delete this file?', { title: 'Delete file', variant: 'danger', confirmLabel: 'Delete' });
            if (!ok) return;
            const r = await fetch('/api/attachment/' + id, {
                method: 'DELETE', credentials: 'same-origin',
                headers: { 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
            });
            if (r.ok || r.status === 204) { window.pvToast && window.pvToast('File deleted.'); window.location.reload(); }
            else { window.pvAlert && window.pvAlert('Delete failed.', { variant: 'danger' }); }
        },

        // ── move / copy into a folder ──
        openActionMenu(kind) { this.actionMenu = kind; },
        async _move(ids, folderId) {
            try {
                const r = await fetch('/web/files/move', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    body: JSON.stringify({ attachment_ids: ids, folder_id: folderId }),
                });
                if (!r.ok) throw new Error(await r.text() || r.statusText);
                window.pvToast && window.pvToast(`Moved ${ids.length} file(s).`);
                window.location.reload();
            } catch (err) {
                window.pvAlert && window.pvAlert('Could not move: ' + (err.message || err), { variant: 'danger' });
            }
        },
        async _copy(ids, folderId) {
            try {
                const r = await fetch('/web/files/copy', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    body: JSON.stringify({ attachment_ids: ids, folder_id: folderId }),
                });
                if (!r.ok) throw new Error(await r.text() || r.statusText);
                const data = await r.json();
                window.pvToast && window.pvToast(`Copied ${data.copied} file(s).`);
                window.location.reload();
            } catch (err) {
                window.pvAlert && window.pvAlert('Could not copy: ' + (err.message || err), { variant: 'danger' });
            }
        },
        chooseActionTarget(folderId) {
            const ids = Array.from(this.selected);
            const kind = this.actionMenu;
            this.actionMenu = null;
            if (!ids.length || !kind) return;
            if (kind === 'move') this._move(ids, folderId);
            else this._copy(ids, folderId);
        },

        // ── upload dialog ──
        uploadTargetLabel() {
            if (this.activeFolderId && this.activeFolderId > 0) {
                const f = this.folderById(this.activeFolderId);
                return f ? f.name : 'folder';
            }
            return 'Unfiled';
        },
        openUploadDialog() { this.uploadOpen = true; this.uploadFiles = []; this.uploadPublic = false; this.uploadError = ''; this.uploadBusy = false; },
        closeUploadDialog() { this.uploadOpen = false; this.uploadFiles = []; this.uploadError = ''; },
        onUploadPick(ev) { this.uploadFiles = Array.from(ev.target.files || []); this.uploadError = ''; },
        async submitUploadDialog() {
            if (!this.uploadFiles.length || this.uploadBusy) return;
            this.uploadBusy = true; this.uploadError = '';
            const folderId = (this.activeFolderId && this.activeFolderId > 0) ? this.activeFolderId : null;
            try {
                for (const file of this.uploadFiles) {
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('public', this.uploadPublic ? '1' : '0');
                    if (folderId) fd.append('folder_id', String(folderId));
                    const r = await fetch('/web/files/picker/upload', {
                        method: 'POST', body: fd, credentials: 'same-origin',
                        headers: { 'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '' },
                    });
                    if (!r.ok) throw new Error(await r.text() || r.statusText);
                }
                this.closeUploadDialog();
                window.pvToast && window.pvToast(`Uploaded ${this.uploadFiles.length} file(s) to ${this.uploadTargetLabel()}.`);
                window.location.reload();
            } catch (err) {
                this.uploadError = 'Upload failed: ' + (err.message || err);
            } finally { this.uploadBusy = false; }
        },
    });
    window.pvFileLibrary = factory;
    const register = () => Alpine.data('pvFileLibrary', factory);
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
