<script>
    (function () {
        if (window.__velmActionBusyInstalled) {
            return;
        }

        window.__velmActionBusyInstalled = true;

        function whenStoreReady(run) {
            if (typeof Alpine !== 'undefined' && Alpine.store('actionBusy')) {
                run();
                return;
            }

            document.addEventListener('alpine:init', () => run(), { once: true });
        }

        document.addEventListener('alpine:init', () => {
            Alpine.store('actionBusy', {
                depth: 0,
                message: '',

                get isOpen() {
                    return this.depth > 0;
                },

                begin(message) {
                    this.depth += 1;

                    if (typeof message === 'string' && message.trim() !== '') {
                        this.message = message.trim();
                    }

                    if (this.depth === 1) {
                        document.body.classList.add('pv-action-busy');
                        this._disableInteractives();
                    }
                },

                end() {
                    this.depth = Math.max(0, this.depth - 1);

                    if (this.depth === 0) {
                        document.body.classList.remove('pv-action-busy');
                        this._restoreInteractives();
                        this.message = '';
                    }
                },

                _disabled: [],

                _selector() {
                    return [
                        'button',
                        'a.pv-btn',
                        '.velm-shell-page-actions a',
                        '.velm-shell-page-actions button',
                        '.pv-form-actions-bar a',
                        '.pv-form-actions-bar button',
                        '.pv-row-action',
                    ].join(', ');
                },

                _disableInteractives() {
                    document.querySelectorAll(this._selector()).forEach((el) => {
                        if (!(el instanceof HTMLElement)) {
                            return;
                        }

                        if (el.dataset.pvActionBusyAllow !== undefined) {
                            return;
                        }

                        const state = {
                            el,
                            wasDisabled: el instanceof HTMLButtonElement || el instanceof HTMLInputElement
                                ? el.disabled
                                : false,
                            tabIndex: el.getAttribute('tabindex'),
                            ariaDisabled: el.getAttribute('aria-disabled'),
                        };

                        if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) {
                            el.disabled = true;
                        } else if (el instanceof HTMLAnchorElement) {
                            el.setAttribute('aria-disabled', 'true');
                            el.setAttribute('tabindex', '-1');
                        }

                        el.classList.add('pv-action-busy-target');
                        this._disabled.push(state);
                    });
                },

                _restoreInteractives() {
                    this._disabled.forEach(({ el, wasDisabled, tabIndex, ariaDisabled }) => {
                        if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) {
                            el.disabled = wasDisabled;
                        }

                        if (tabIndex === null) {
                            el.removeAttribute('tabindex');
                        } else {
                            el.setAttribute('tabindex', tabIndex);
                        }

                        if (ariaDisabled === null) {
                            el.removeAttribute('aria-disabled');
                        } else {
                            el.setAttribute('aria-disabled', ariaDisabled);
                        }

                        el.classList.remove('pv-action-busy-target');
                    });

                    this._disabled = [];
                },
            });
        });

        window.pvWithActionBusy = async function (fn, options = {}) {
            if (typeof fn !== 'function') {
                return;
            }

            const message = typeof options.message === 'string' ? options.message : @js(__('Working…'));

            return new Promise((resolve, reject) => {
                whenStoreReady(async () => {
                    Alpine.store('actionBusy').begin(message);

                    try {
                        resolve(await fn());
                    } catch (error) {
                        reject(error);
                    } finally {
                        Alpine.store('actionBusy').end();
                    }
                });
            });
        };

        window.pvExecuteViewAction = async function (config) {
            const url = config?.url;
            const method = (config?.method || 'POST').toUpperCase();
            const label = config?.label || @js(__('Action'));
            const confirm = config?.confirm || '';
            const message = config?.message || label;

            if (! url) {
                return;
            }

            if (confirm) {
                const ok = await window.pvConfirm(confirm, {
                    title: label,
                    confirmLabel: @js(__('Continue')),
                });

                if (! ok) {
                    return;
                }
            }

            await window.pvWithActionBusy(async () => {
                const response = await fetch(url, {
                    method,
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '',
                        Accept: 'application/json',
                    },
                });

                let payload = null;

                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }

                if (response.ok || response.status === 204) {
                    if (payload?.redirect) {
                        window.location.href = payload.redirect;
                        return;
                    }

                    if (payload?.message && window.pvToast) {
                        window.pvToast(payload.message);
                    }

                    window.location.reload();
                    return;
                }

                window.pvAlert(payload?.message || @js(__('Action failed.')), { variant: 'danger' });
            }, { message });
        };
    })();
</script>
