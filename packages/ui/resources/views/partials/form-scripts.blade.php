<script>
    (function () {
        function embedUrl(fullPageUrl) {
            const url = new URL(fullPageUrl, window.location.origin);
            url.searchParams.set('embed', '1');

            return url.pathname + url.search + url.hash;
        }

        function dialogTarget() {
            if (window.parent !== window && window.parent.Alpine?.store('recordDialog')) {
                return window.parent;
            }

            return window;
        }

        window.pvCloseRecordDialog = function () {
            const target = dialogTarget();
            target.Alpine?.store('recordDialog')?.close();
        };

        window.pvOpenRecord = function (fullPageUrl, title) {
            if (!fullPageUrl) {
                return;
            }

            const target = dialogTarget();
            const store = target.Alpine?.store('recordDialog');

            if (!store) {
                target.location.href = fullPageUrl;

                return;
            }

            store.show(embedUrl(fullPageUrl), fullPageUrl, title || '');
        };
    })();

    function createVelmAppsCatalogStore() {
        return {
            query: '',
            stateFilter: '',
            categoryFilter: '',
            visibleCount: 0,

            setStateFilter(key) {
                this.stateFilter = key;
                this.apply();
            },

            setCategoryFilter(key) {
                this.categoryFilter = key;
                this.apply();
            },

            apply() {
                const q = (this.query || '').trim().toLowerCase();
                let visible = 0;

                document.querySelectorAll('[data-velm-app]').forEach((card) => {
                    const stateOk = ! this.stateFilter || card.dataset.velmAppState === this.stateFilter;
                    const catOk = ! this.categoryFilter || card.dataset.velmAppCategory === this.categoryFilter;
                    const queryOk = ! q || (card.dataset.velmAppHaystack || '').includes(q);
                    const show = stateOk && catOk && queryOk;
                    card.style.display = show ? '' : 'none';

                    if (show) {
                        visible++;
                    }
                });

                this.visibleCount = visible;
            },

            reset() {
                this.query = '';
                this.stateFilter = '';
                this.categoryFilter = '';
                this.apply();
            },
        };
    }

    function ensureVelmAppsCatalogStore() {
        if (typeof Alpine === 'undefined') {
            return;
        }

        if (Alpine.store('velmAppsCatalog')) {
            return;
        }

        Alpine.store('velmAppsCatalog', createVelmAppsCatalogStore());
    }

    document.addEventListener('livewire:navigating', ensureVelmAppsCatalogStore);
    document.addEventListener('livewire:navigated', () => {
        ensureVelmAppsCatalogStore();
        Alpine.store('velmAppsCatalog')?.apply();
    });

    document.addEventListener('alpine:init', () => {
        ensureVelmAppsCatalogStore();

        Alpine.data('velmAppsCatalogHost', () => ({
            init() {
                ensureVelmAppsCatalogStore();
                this.$nextTick(() => {
                    this.$store.velmAppsCatalog?.apply();
                    this.$refs.searchInput?.focus();
                });
            },

            get query() {
                return this.$store.velmAppsCatalog?.query ?? '';
            },

            set query(value) {
                if (this.$store.velmAppsCatalog) {
                    this.$store.velmAppsCatalog.query = value;
                }
            },

            get visibleCount() {
                return this.$store.velmAppsCatalog?.visibleCount ?? 0;
            },

            get hasActiveFilters() {
                const store = this.$store.velmAppsCatalog;
                if (! store) {
                    return false;
                }

                return !!(store.query || store.stateFilter || store.categoryFilter);
            },

            applyFilters() {
                this.$store.velmAppsCatalog?.apply();
            },

            clearFilters() {
                this.$store.velmAppsCatalog?.reset();
                this.$refs.searchInput?.focus();
            },
        }));

        Alpine.data('pvM2o', (cfg) => ({
            wireKey: cfg.wireKey,
            comodel: cfg.comodel,
            searchUrl: cfg.searchUrl,
            formViewUrl: cfg.formViewUrl || null,
            createUrl: cfg.createUrl || null,
            readonly: !!cfg.readonly,
            canQuickCreate: !!cfg.canQuickCreate,

            value: cfg.initialId != null ? Number(cfg.initialId) : null,
            label: cfg.initialLabel || '',
            query: '',
            results: [],
            cursor: 0,
            open: false,
            loading: false,
            _abort: null,
            _initialFetched: false,

            init() {
                this.query = this.label;
                this.syncWire();
            },

            get exactMatch() {
                const q = this.query.trim().toLowerCase();
                if (!q) return null;
                return this.results.find((r) => r.label.toLowerCase() === q) || null;
            },

            get createCandidate() {
                if (this.readonly || !this.canQuickCreate) return false;
                const q = this.query.trim();
                return q.length > 0 && !this.exactMatch;
            },

            get canCreateAndEdit() {
                return !this.readonly && !!this.createUrl;
            },

            get createAndEditIndex() {
                return this.results.length + (this.createCandidate ? 1 : 0);
            },

            syncWire() {
                if (!this.wireKey) return;
                this.$wire.set(this.wireKey, this.value);
            },

            async fetchResults() {
                if (this._abort) this._abort.abort();
                const ctl = new AbortController();
                this._abort = ctl;
                this.loading = true;
                try {
                    const url = this.searchUrl + '&q=' + encodeURIComponent(this.query);
                    const r = await fetch(url, { signal: ctl.signal, credentials: 'same-origin' });
                    if (!r.ok) throw new Error('fetch failed');
                    const data = await r.json();
                    this.results = data.results || [];
                    this.cursor = 0;
                } catch (e) {
                    if (e.name !== 'AbortError') this.results = [];
                } finally {
                    this.loading = false;
                }
            },

            onFocus() {
                this.open = true;
                if (!this._initialFetched) {
                    this._initialFetched = true;
                    this.fetchResults();
                }
            },

            onInput() {
                this.open = true;
                if (this.query !== this.label) {
                    this.value = null;
                    this.label = '';
                    this.syncWire();
                }
                this.fetchResults();
            },

            close() {
                this.open = false;
                this.query = this.label;
            },

            moveCursor(delta) {
                if (!this.open) {
                    this.open = true;
                    return;
                }
                const extra = (this.createCandidate ? 1 : 0) + (this.canCreateAndEdit ? 1 : 0);
                const max = this.results.length + extra - 1;
                if (max < 0) return;
                this.cursor = Math.max(0, Math.min(max, this.cursor + delta));
            },

            onEnter() {
                if (!this.open) return;
                if (this.cursor < this.results.length) {
                    this.pick(this.results[this.cursor]);
                } else if (this.createCandidate && this.cursor === this.results.length) {
                    this.createFromQuery();
                } else if (this.canCreateAndEdit) {
                    this.createAndEdit();
                }
            },

            pick(item) {
                this.value = item.id;
                this.label = item.label;
                this.query = item.label;
                this.open = false;
                this.syncWire();
            },

            clearSelection() {
                this.value = null;
                this.label = '';
                this.query = '';
                this.cursor = 0;
                this.open = false;
                this.syncWire();
                this.$refs.input?.focus();
            },

            recordUrl(id) {
                if (!this.formViewUrl || id == null) return null;
                return this.formViewUrl.replace(/\/$/, '') + '/' + id;
            },

            openRecord() {
                if (this.value === null || !this.formViewUrl) return;
                const url = this.recordUrl(this.value);
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, this.label);
                } else if (url) {
                    window.location.href = url;
                }
            },

            async createFromQuery() {
                const name = this.query.trim();
                if (!name) return;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                try {
                    const r = await fetch('/api/m2o/quick-create', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ model: this.comodel, name }),
                    });
                    if (r.status === 400 && this.createUrl) {
                        this.createAndEdit();
                        return;
                    }
                    if (!r.ok) return;
                    const item = await r.json();
                    this.results = [item, ...this.results.filter((x) => x.id !== item.id)];
                    this.pick(item);
                } catch (_) {}
            },

            createAndEdit() {
                if (!this.createUrl) return;
                this.open = false;
                const q = (this.query || '').trim();
                const url = this.createUrl + (q ? '?name=' + encodeURIComponent(q) : '');
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, q || '{{ __('New record') }}');
                } else {
                    window.location.href = url;
                }
            },
        }));

        Alpine.data('pvM2m', (cfg) => ({
            wireKey: cfg.wireKey,
            comodel: cfg.comodel,
            searchUrl: cfg.searchUrl,
            formViewUrl: cfg.formViewUrl || null,
            readonly: !!cfg.readonly,
            dialogOnly: !!cfg.dialogOnly,
            canQuickCreate: !!cfg.canQuickCreate,
            linkOpen: false,
            selected: Array.isArray(cfg.initial) ? cfg.initial.slice() : [],
            query: '',
            results: [],
            cursor: 0,
            open: false,
            loading: false,
            _abort: null,

            init() {
                this.syncWire();
                window.addEventListener('velm-dialog-saved', (event) => this.onDialogSaved(event));
            },

            get exactMatch() {
                const q = this.query.trim().toLowerCase();
                if (!q) return null;
                return this.results.find((r) => r.label.toLowerCase() === q) || null;
            },

            get createCandidate() {
                if (this.readonly || !this.canQuickCreate) return false;
                const q = this.query.trim();
                return q.length > 0 && !this.exactMatch;
            },

            syncWire() {
                if (!this.wireKey || this.readonly) return;
                this.$wire.set(this.wireKey, this.selected.map((s) => s.id));
            },

            async searchNow() {
                if (this.readonly) return;
                if (this._abort) this._abort.abort();
                const ctl = new AbortController();
                this._abort = ctl;
                this.loading = true;
                try {
                    const url = this.searchUrl + '&q=' + encodeURIComponent(this.query);
                    const r = await fetch(url, { signal: ctl.signal, credentials: 'same-origin' });
                    if (!r.ok) throw new Error('fetch failed');
                    const data = await r.json();
                    this.results = data.results || [];
                    this.cursor = 0;
                    this.open = true;
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        this.results = [];
                        this.open = false;
                    }
                } finally {
                    this.loading = false;
                }
            },

            onFocus() {
                if (this.readonly) return;
                this.open = true;
                this.searchNow();
            },

            filteredResults() {
                const taken = new Set(this.selected.map((s) => s.id));
                return this.results.filter((r) => !taken.has(r.id));
            },

            moveCursor(delta) {
                if (!this.open) {
                    this.open = true;
                    return;
                }
                const opts = this.filteredResults();
                const extra = (this.createCandidate ? 1 : 0);
                const max = opts.length + extra - 1;
                if (max < 0) return;
                this.cursor = Math.max(0, Math.min(max, this.cursor + delta));
            },

            onEnter() {
                if (!this.open) return;
                const opts = this.filteredResults();
                if (this.cursor < opts.length) {
                    this.add(opts[this.cursor]);
                } else if (this.createCandidate) {
                    this.createFromQuery();
                }
            },

            add(item) {
                if (this.readonly) return;
                if (this.selected.some((s) => s.id === item.id)) return;
                this.selected.push(item);
                this.query = '';
                this.results = [];
                this.open = false;
                this.linkOpen = false;
                this.syncWire();
            },

            remove(id) {
                if (this.readonly) return;
                this.selected = this.selected.filter((s) => s.id !== id);
                this.syncWire();
            },

            openChip(item) {
                if (!this.formViewUrl || !item?.id) return;
                const url = this.formViewUrl.replace(/\/$/, '') + '/' + item.id + '/edit';
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, item.label || '');
                } else {
                    window.location.href = url;
                }
            },

            async createFromQuery() {
                const name = this.query.trim();
                if (!name) return;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                try {
                    const r = await fetch('/api/m2o/quick-create', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ model: this.comodel, name }),
                    });
                    if (!r.ok) return;
                    const item = await r.json();
                    this.add(item);
                } catch (_) {}
            },

            createAndEdit() {
                if (!this.formViewUrl) return;
                const q = (this.query || '').trim();
                const url = this.formViewUrl.replace(/\/$/, '') + '/create' + (q ? '?name=' + encodeURIComponent(q) : '');
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, q || '{{ __('New record') }}');
                } else {
                    window.location.href = url;
                }
            },

            onDialogSaved(event) {
                const detail = event?.detail;
                if (!detail || detail.model !== this.comodel || detail.id == null) return;
                const id = Number(detail.id);
                const label = detail.label || String(id);
                if (!this.selected.some((s) => s.id === id)) {
                    this.add({ id, label });
                }
            },
        }));

        Alpine.data('pvO2mDialog', (cfg) => ({
            wireKey: cfg.wireKey,
            comodel: cfg.comodel,
            inverseName: cfg.inverseName,
            searchUrl: cfg.searchUrl,
            formViewUrl: cfg.formViewUrl || null,
            columns: cfg.columns || [],
            rows: Array.isArray(cfg.rows) ? cfg.rows.map((r) => ({ ...r })) : [],
            parentRecordId: cfg.parentRecordId,
            readonly: !!cfg.readonly,
            query: '',
            results: [],
            cursor: 0,
            open: false,
            linkOpen: false,
            loading: false,
            _abort: null,

            init() {
                this.syncWire();
            },

            syncWire() {
                if (!this.wireKey || this.readonly) return;
                this.$wire.set(this.wireKey, this.rows.map((r) => r.id));
            },

            recordUrl(id, edit = false) {
                if (!this.formViewUrl || id == null) return null;
                const base = this.formViewUrl.replace(/\/$/, '') + '/' + id;
                return edit ? base + '/edit' : base;
            },

            openRecord(id, label) {
                const url = this.recordUrl(id);
                if (!url) return;
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, label || '');
                } else {
                    window.location.href = url;
                }
            },

            createNew() {
                if (!this.formViewUrl || !this.parentRecordId) return;
                const url = this.formViewUrl.replace(/\/$/, '') + '/create?' + encodeURIComponent(this.inverseName) + '=' + this.parentRecordId;
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, '{{ __('New line') }}');
                } else {
                    window.location.href = url;
                }
            },

            async searchNow() {
                if (this._abort) this._abort.abort();
                const ctl = new AbortController();
                this._abort = ctl;
                this.loading = true;
                try {
                    const r = await fetch(this.searchUrl + '&q=' + encodeURIComponent(this.query), {
                        signal: ctl.signal,
                        credentials: 'same-origin',
                    });
                    if (!r.ok) throw new Error('fetch failed');
                    const data = await r.json();
                    this.results = data.results || [];
                    this.cursor = 0;
                    this.open = true;
                } catch (e) {
                    if (e.name !== 'AbortError') this.results = [];
                } finally {
                    this.loading = false;
                }
            },

            filteredResults() {
                const taken = new Set(this.rows.map((r) => r.id));
                return this.results.filter((r) => !taken.has(r.id));
            },

            moveCursor(delta) {
                const opts = this.filteredResults();
                const max = opts.length - 1;
                if (max < 0) return;
                this.cursor = Math.max(0, Math.min(max, this.cursor + delta));
            },

            onEnter() {
                const opts = this.filteredResults();
                if (this.cursor < opts.length) {
                    this.add(opts[this.cursor]);
                }
            },

            add(item) {
                if (this.readonly) return;
                const row = { id: item.id, label: item.label };
                this.columns.forEach((col) => {
                    if (col.name !== 'id' && row[col.name] === undefined) {
                        row[col.name] = item.label;
                    }
                });
                this.rows.push(row);
                this.query = '';
                this.open = false;
                this.linkOpen = false;
                this.syncWire();
            },

            remove(id) {
                if (this.readonly) return;
                this.rows = this.rows.filter((r) => r.id !== id);
                this.syncWire();
            },
        }));

        Alpine.data('pvO2mInline', (cfg) => ({
            wireKey: cfg.wireKey,
            comodel: cfg.comodel,
            inverseName: cfg.inverseName,
            searchUrl: cfg.searchUrl,
            formViewUrl: cfg.formViewUrl || null,
            recordsApiUrl: cfg.recordsApiUrl || '/api/records',
            columns: cfg.columns || [],
            rows: Array.isArray(cfg.rows) ? cfg.rows.map((r) => ({ ...r })) : [],
            parentRecordId: cfg.parentRecordId,
            readonly: !!cfg.readonly,
            query: '',
            results: [],
            cursor: 0,
            open: false,
            linkOpen: false,
            loading: false,
            patching: false,
            patchError: '',
            _abort: null,

            init() {
                this.syncWire();
            },

            syncWire() {
                if (!this.wireKey || this.readonly) return;
                this.$wire.set(this.wireKey, this.rows.map((r) => r.id));
            },

            formatCell(row, col) {
                const value = row[col.name];
                if (col.kind === 'boolean') {
                    return value ? '{{ __('Yes') }}' : '{{ __('No') }}';
                }
                if (value === null || value === undefined || value === false) {
                    return '—';
                }
                return String(value);
            },

            recordUrl(id, edit = false) {
                if (!this.formViewUrl || id == null) return null;
                const base = this.formViewUrl.replace(/\/$/, '') + '/' + id;
                return edit ? base + '/edit' : base;
            },

            openRecord(id, label) {
                const url = this.recordUrl(id);
                if (!url) return;
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, label || '');
                } else {
                    window.location.href = url;
                }
            },

            createNew() {
                if (!this.formViewUrl || !this.parentRecordId) return;
                const url = this.formViewUrl.replace(/\/$/, '') + '/create?' + encodeURIComponent(this.inverseName) + '=' + this.parentRecordId;
                if (window.pvOpenRecord) {
                    window.pvOpenRecord(url, '{{ __('New line') }}');
                } else {
                    window.location.href = url;
                }
            },

            async patchCell(row, field, value) {
                if (this.readonly || this.patching) return;
                this.patchError = '';
                this.patching = true;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const url = this.recordsApiUrl.replace(/\/$/, '') + '/' + row.id + '?model=' + encodeURIComponent(this.comodel);
                try {
                    const r = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ [field]: value }),
                    });
                    if (!r.ok) {
                        const data = await r.json().catch(() => ({}));
                        throw new Error(data.message || '{{ __('Could not save line.') }}');
                    }
                    const data = await r.json();
                    if (data && typeof data === 'object') {
                        Object.keys(data).forEach((key) => {
                            if (key !== 'id' && Object.prototype.hasOwnProperty.call(row, key)) {
                                row[key] = data[key];
                            }
                        });
                    }
                } catch (e) {
                    this.patchError = e.message || '{{ __('Could not save line.') }}';
                } finally {
                    this.patching = false;
                }
            },

            async searchNow() {
                if (this._abort) this._abort.abort();
                const ctl = new AbortController();
                this._abort = ctl;
                this.loading = true;
                try {
                    const r = await fetch(this.searchUrl + '&q=' + encodeURIComponent(this.query), {
                        signal: ctl.signal,
                        credentials: 'same-origin',
                    });
                    if (!r.ok) throw new Error('fetch failed');
                    const data = await r.json();
                    this.results = data.results || [];
                    this.cursor = 0;
                    this.open = true;
                } catch (e) {
                    if (e.name !== 'AbortError') this.results = [];
                } finally {
                    this.loading = false;
                }
            },

            filteredResults() {
                const taken = new Set(this.rows.map((r) => r.id));
                return this.results.filter((r) => !taken.has(r.id));
            },

            moveCursor(delta) {
                const opts = this.filteredResults();
                const max = opts.length - 1;
                if (max < 0) return;
                this.cursor = Math.max(0, Math.min(max, this.cursor + delta));
            },

            onEnter() {
                const opts = this.filteredResults();
                if (this.cursor < opts.length) {
                    this.add(opts[this.cursor]);
                }
            },

            add(item) {
                if (this.readonly) return;
                const row = { id: item.id, label: item.label };
                this.columns.forEach((col) => {
                    if (col.name !== 'id' && row[col.name] === undefined) {
                        row[col.name] = item[col.name] ?? item.label;
                    }
                });
                this.rows.push(row);
                this.query = '';
                this.open = false;
                this.linkOpen = false;
                this.syncWire();
            },

            remove(id) {
                if (this.readonly) return;
                this.rows = this.rows.filter((r) => r.id !== id);
                this.syncWire();
            },
        }));

        Alpine.data('pvFormNotebook', (storageKey, defaultTab, pageNames) => ({
            tab: defaultTab,
            init() {
                try {
                    const saved = localStorage.getItem(storageKey);
                    if (saved && pageNames.includes(saved)) {
                        this.tab = saved;
                    }
                } catch (_) {}
            },
            pick(name) {
                this.tab = name;
                try {
                    localStorage.setItem(storageKey, name);
                } catch (_) {}
            },
        }));

        window.pvWireGet = function (alpine, key) {
            if (!alpine?.$wire || !key) {
                return undefined;
            }
            const wire = alpine.$wire;
            let value;
            if (typeof wire.$get === 'function') {
                value = wire.$get(key);
            } else if (typeof wire.get === 'function') {
                value = wire.get(key);
            }
            if (value !== undefined && value !== null && String(value) !== '') {
                return value;
            }

            return undefined;
        };

        window.pvWireSet = function (alpine, key, value) {
            if (!alpine?.$wire || !key) {
                return;
            }
            const wire = alpine.$wire;
            if (typeof wire.set === 'function') {
                wire.set(key, value);
            } else if (typeof wire.$set === 'function') {
                wire.$set(key, value);
            }
        };

        Alpine.data('pvFilePickerField', (cfg) => ({
            wireKey: cfg.wireKey || '',
            multi: !!cfg.multi,
            readonly: !!cfg.readonly,
            accept: cfg.accept || '',
            pickerTitle: cfg.pickerTitle || '{{ __('Pick a file') }}',
            selected: (cfg.initial || []).slice(),

            init() {
                if (typeof this.$wire === 'undefined' || !this.wireKey) {
                    return;
                }

                this.$watch('selected', () => this.syncWire(), { deep: true });
            },

            syncWire() {
                if (typeof this.$wire === 'undefined' || !this.wireKey) {
                    return;
                }

                if (this.multi) {
                    this.$wire.set(
                        this.wireKey,
                        this.selected.map((row) => row.id),
                    );
                    return;
                }

                this.$wire.set(
                    this.wireKey,
                    this.selected.length ? this.selected[0].id : null,
                );
            },

            normalizeRow(row) {
                if (!row || !row.id) {
                    return null;
                }

                const mime = String(row.mimetype || '').toLowerCase();
                const download = row.download_url || ('/api/attachment/' + row.id + '/download');
                let thumbnail = row.thumbnail_url || '';

                if (!thumbnail && mime.startsWith('image/')) {
                    thumbnail = download;
                }

                return {
                    id: row.id,
                    name: row.name || ('#' + row.id),
                    mimetype: row.mimetype || '',
                    thumbnail_url: thumbnail,
                    download_url: download,
                };
            },

            openPicker() {
                if (this.readonly) {
                    return;
                }

                const params = new URLSearchParams({ multi: this.multi ? '1' : '0' });

                if (this.accept) {
                    params.set('accept', this.accept);
                }

                const url = '/web/files/picker?' + params.toString();

                if (!window.PvDialog) {
                    window.location.href = url;
                    return;
                }

                window.PvDialog.open({
                    url,
                    title: this.pickerTitle,
                    onResult: (result) => {
                        if (!result) {
                            return;
                        }

                        if (this.multi && Array.isArray(result)) {
                            result.forEach((row) => this.addRow(row));
                            return;
                        }

                        this.addRow(result);
                    },
                });
            },

            addRow(row) {
                const normalized = this.normalizeRow(row);

                if (!normalized) {
                    return;
                }

                if (this.multi) {
                    if (this.selected.some((item) => item.id === normalized.id)) {
                        return;
                    }

                    this.selected.push(normalized);
                    this.syncWire();

                    return;
                }

                this.selected = [normalized];
                this.syncWire();
            },

            remove(id) {
                if (this.readonly) {
                    return;
                }

                this.selected = this.selected.filter((row) => row.id !== id);
                this.syncWire();
            },
        }));

        Alpine.data('pvFileUrl', (cfg) => ({
            wireKey: cfg.wireKey || '',
            fallbackWireKey: cfg.fallbackWireKey || '',
            accept: cfg.accept || 'image/*',
            readonly: !!cfg.readonly,
            pickerTitle: cfg.pickerTitle || '{{ __('Choose file') }}',
            value: cfg.initial || '',

            init() {
                if (typeof this.$wire === 'undefined' || !this.wireKey) {
                    return;
                }
                const wireVal = this.$wire.get(this.wireKey);
                if (wireVal !== undefined && wireVal !== null && String(wireVal) !== this.value) {
                    this.value = String(wireVal);
                }
                this.$watch('value', (v) => {
                    this.$wire.set(this.wireKey, v ?? '');
                });
                if (this.fallbackWireKey) {
                    this.$wire.$watch(this.fallbackWireKey, () => {});
                }
            },

            resolvedUrl() {
                const own = (this.value || '').trim();
                if (own !== '') {
                    return own;
                }
                if (this.fallbackWireKey && typeof this.$wire !== 'undefined') {
                    return String(this.$wire.get(this.fallbackWireKey) || '').trim();
                }
                return '';
            },

            get previewUrl() {
                const v = this.resolvedUrl();
                if (!v || !this.looksLikeImage(v)) {
                    return '';
                }
                return v;
            },

            looksLikeImage(url) {
                if (/^data:image\//i.test(url)) {
                    return true;
                }
                if (/\/api\/attachment\/\d+\/download/i.test(url)) {
                    return true;
                }
                return /\.(png|jpe?g|gif|webp|svg|ico)(\?|$)/i.test(url);
            },

            pick() {
                if (this.readonly) {
                    return;
                }
                const params = new URLSearchParams({ accept: this.accept });
                const url = '/web/files/picker?' + params.toString();
                if (!window.PvDialog) {
                    window.location.href = url;
                    return;
                }
                window.PvDialog.open({
                    url,
                    title: this.pickerTitle,
                    onResult: (row) => this.applyPick(row),
                });
            },

            async applyPick(row) {
                if (!row || !row.id) {
                    return;
                }
                const downloadUrl = '/api/attachment/' + row.id + '/download';
                try {
                    await fetch('/web/files/bulk/public', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ ids: [row.id], public: true }),
                    });
                } catch (_) {}
                this.value = downloadUrl;
            },

            clear() {
                if (this.readonly) {
                    return;
                }
                this.value = '';
            },
        }));
    });

    document.addEventListener('keydown', (event) => {
        if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') {
            return;
        }

        const form = document.getElementById('velm-form');

        if (!form) {
            return;
        }

        event.preventDefault();
        form.requestSubmit();
    });
</script>
