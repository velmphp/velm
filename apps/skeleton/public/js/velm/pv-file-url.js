(function () {
    const factory = (cfg) => ({
        wireKey: cfg.wireKey || '',
        accept: cfg.accept || 'image/*',
        readonly: !!cfg.readonly,
        pickerTitle: cfg.pickerTitle || 'Choose file',
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
        },

        get previewUrl() {
            const v = (this.value || '').trim();
            if (!v || !this.looksLikeImage(v)) {
                return '';
            }
            return v;
        },

        looksLikeImage(url) {
            if (/^data:image\//i.test(url)) return true;
            if (/\/api\/attachment\/\d+\/download/i.test(url)) return true;
            return /\.(png|jpe?g|gif|webp|svg|ico)(\?|$)/i.test(url);
        },

        pick() {
            if (this.readonly) return;
            const params = new URLSearchParams({ accept: this.accept });
            const url = '/web/files/picker?' + params.toString();
            const title = this.pickerTitle;
            if (!window.PvDialog) {
                window.location.href = url;
                return;
            }
            window.PvDialog.open({
                url,
                title,
                onResult: (row) => this.applyPick(row),
            });
        },

        async applyPick(row) {
            if (!row || !row.id) return;
            const downloadUrl = '/api/attachment/' + row.id + '/download';
            try {
                await fetch('/web/files/bulk/public', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '',
                    },
                    body: JSON.stringify({ ids: [row.id], public: true }),
                });
            } catch (_) {
                /* branding URLs should be public; non-fatal if ACL denies */
            }
            this.value = downloadUrl;
        },

        clear() {
            if (this.readonly) return;
            this.value = '';
        },
    });

    const register = () => {
        Alpine.data('pvFileUrl', factory);
    };

    window.pvFileUrl = factory;
    document.addEventListener('alpine:init', register);
    if (typeof Alpine !== 'undefined') {
        register();
    }
})();
