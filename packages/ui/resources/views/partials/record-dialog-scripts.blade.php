<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('recordDialog', {
            isOpen: false,
            iframeUrl: null,
            fullPageUrl: null,
            title: '',
            posX: null,
            posY: null,
            _dragging: false,
            _dragOffsetX: 0,
            _dragOffsetY: 0,

            show(iframeUrl, fullPageUrl, title) {
                this.iframeUrl = iframeUrl;
                this.fullPageUrl = fullPageUrl;
                this.title = title;
                this.isOpen = true;
                document.body.classList.add('pv-record-dialog-open');
            },

            close() {
                this.isOpen = false;
                this.iframeUrl = null;
                this.fullPageUrl = null;
                this.title = '';
                document.body.classList.remove('pv-record-dialog-open');
            },

            panelStyle() {
                if (this.posX === null || this.posY === null) {
                    return '';
                }

                return `left:${this.posX}px;top:${this.posY}px;transform:none;margin:0;`;
            },

            startDrag(event) {
                if (event.button !== 0) {
                    return;
                }

                const panel = event.currentTarget.closest('.pv-record-dialog-panel');
                if (!panel) {
                    return;
                }

                const rect = panel.getBoundingClientRect();

                if (this.posX === null) {
                    this.posX = rect.left;
                    this.posY = rect.top;
                }

                this._dragging = true;
                this._dragOffsetX = event.clientX - this.posX;
                this._dragOffsetY = event.clientY - this.posY;

                const onMove = (e) => {
                    if (!this._dragging) {
                        return;
                    }
                    this.posX = Math.max(8, e.clientX - this._dragOffsetX);
                    this.posY = Math.max(8, e.clientY - this._dragOffsetY);
                };

                const onUp = () => {
                    this._dragging = false;
                    window.removeEventListener('mousemove', onMove);
                    window.removeEventListener('mouseup', onUp);
                };

                window.addEventListener('mousemove', onMove);
                window.addEventListener('mouseup', onUp);
            },
        });

        window.PvDialog = {
            _onResult: null,

            open({ url, title, onResult }) {
                this._onResult = typeof onResult === 'function' ? onResult : null;
                let iframeUrl = url;
                try {
                    const parsed = new URL(url, window.location.origin);
                    if (! parsed.searchParams.has('embed')) {
                        parsed.searchParams.set('embed', '1');
                    }
                    iframeUrl = parsed.pathname + parsed.search;
                } catch (_) {
                    /* keep raw url */
                }
                Alpine.store('recordDialog').show(
                    iframeUrl,
                    null,
                    title || '{{ __('Choose file') }}',
                );
            },

            close(result) {
                const callback = this._onResult;
                this._onResult = null;
                Alpine.store('recordDialog')?.close();
                if (callback) {
                    callback(result);
                }
            },
        };

        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) {
                return;
            }

            if (event.data?.type === 'velm-picker-picked') {
                window.PvDialog?.close(event.data.row);

                return;
            }

            if (event.data?.type !== 'velm-dialog-saved') {
                return;
            }

            window.dispatchEvent(new CustomEvent('velm-dialog-saved', { detail: event.data }));
        });
    });
</script>
