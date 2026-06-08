<script>
    (function () {
        if (window.__velmConfirmDialogInstalled) {
            return;
        }

        window.__velmConfirmDialogInstalled = true;

        const PV_DIALOG_LEAVE_MS = 220;

        function whenStoreReady(run) {
            if (typeof Alpine !== 'undefined' && Alpine.store('confirmDialog')) {
                run();
                return;
            }

            document.addEventListener('alpine:init', () => run(), { once: true });
        }

        window.pvConfirm = function (message, options = {}) {
            return new Promise((resolve) => {
                whenStoreReady(() => {
                    Alpine.store('confirmDialog').ask(message, options, resolve);
                });
            });
        };

        window.pvAlert = function (message, options = {}) {
            return new Promise((resolve) => {
                whenStoreReady(() => {
                    Alpine.store('confirmDialog').alert(message, options, resolve);
                });
            });
        };

        window.pvPrompt = function (message, defaultValue = '', options = {}) {
            return new Promise((resolve) => {
                whenStoreReady(() => {
                    Alpine.store('confirmDialog').prompt(message, defaultValue, options, resolve);
                });
            });
        };

        document.addEventListener('alpine:init', () => {
            Alpine.store('confirmDialog', {
                isOpen: false,
                mode: 'confirm',
                title: '',
                message: '',
                confirmLabel: @js(__('Continue')),
                cancelLabel: @js(__('Cancel')),
                variant: 'primary',
                promptExpected: '',
                promptValue: '',
                posX: null,
                posY: null,
                _dragging: false,
                _dragOffsetX: 0,
                _dragOffsetY: 0,
                _resolve: null,

                panelStyle() {
                    if (this.posX === null || this.posY === null) {
                        return '';
                    }

                    return `left:${this.posX}px;top:${this.posY}px;right:auto;bottom:auto;transform:none;margin:0;`;
                },

                confirmButtonClass() {
                    const classes = {
                        primary: 'pv-btn-primary',
                        secondary: 'pv-btn-secondary',
                        success: 'pv-btn-success',
                        warning: 'pv-btn-warning',
                        danger: 'pv-btn-danger',
                    };

                    return `pv-btn ${classes[this.variant] || classes.primary}`;
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

                ask(message, options, resolve) {
                    this.mode = 'confirm';
                    this.message = String(message ?? '');
                    this.title = options.title || @js(__('Confirm'));
                    this.confirmLabel = options.confirmLabel || @js(__('Continue'));
                    this.cancelLabel = options.cancelLabel || @js(__('Cancel'));
                    this.variant = options.variant || 'primary';
                    this.promptExpected = options.promptExpected || '';
                    this.promptValue = '';
                    this._resolve = resolve;
                    this.isOpen = true;
                    document.body.classList.add('pv-record-dialog-open');
                },

                alert(message, options, resolve) {
                    this.mode = 'alert';
                    this.message = String(message ?? '');
                    this.title = options.title || @js(__('Notice'));
                    this.confirmLabel = options.confirmLabel || @js(__('OK'));
                    this.cancelLabel = @js(__('Cancel'));
                    this.variant = options.variant || 'primary';
                    this.promptExpected = '';
                    this.promptValue = '';
                    this._resolve = resolve;
                    this.isOpen = true;
                    document.body.classList.add('pv-record-dialog-open');
                },

                prompt(message, defaultValue, options, resolve) {
                    this.mode = 'prompt';
                    this.message = String(message ?? '');
                    this.title = options.title || @js(__('Input'));
                    this.confirmLabel = options.confirmLabel || @js(__('OK'));
                    this.cancelLabel = options.cancelLabel || @js(__('Cancel'));
                    this.variant = options.variant || 'primary';
                    this.promptExpected = '';
                    this.promptValue = String(defaultValue ?? '');
                    this._resolve = resolve;
                    this.isOpen = true;
                    document.body.classList.add('pv-record-dialog-open');
                },

                promptMatches() {
                    if (!this.promptExpected) {
                        return true;
                    }

                    return this.promptValue === this.promptExpected;
                },

                canAccept() {
                    if (this.mode === 'prompt') {
                        return true;
                    }

                    return this.promptMatches();
                },

                accept() {
                    if (!this.canAccept()) {
                        return;
                    }

                    if (this.mode === 'prompt') {
                        const resolve = this._resolve;
                        const value = this.promptValue;
                        this._resolve = null;
                        this.finish(true);
                        if (resolve) {
                            resolve(value);
                        }
                        return;
                    }

                    if (this.mode !== 'confirm') {
                        return;
                    }

                    const resolve = this._resolve;
                    this._resolve = null;
                    this.finish(true);
                    if (resolve) {
                        resolve(true);
                    }
                },

                cancel() {
                    if (this.mode === 'prompt') {
                        const resolve = this._resolve;
                        this._resolve = null;
                        this.finish(false);
                        if (resolve) {
                            resolve(null);
                        }
                        return;
                    }

                    if (this.mode !== 'confirm') {
                        return;
                    }

                    const resolve = this._resolve;
                    this._resolve = null;
                    this.finish(false);
                    if (resolve) {
                        resolve(false);
                    }
                },

                acknowledge() {
                    if (this.mode !== 'alert') {
                        return;
                    }

                    const resolve = this._resolve;
                    this._resolve = null;
                    this.finish(true);
                    if (resolve) {
                        resolve(true);
                    }
                },

                dismiss() {
                    if (this.mode === 'alert') {
                        this.acknowledge();
                        return;
                    }

                    this.cancel();
                },

                finish() {
                    this.isOpen = false;

                    window.setTimeout(() => {
                        this.title = '';
                        this.message = '';
                        this.promptExpected = '';
                        this.promptValue = '';
                        document.body.classList.remove('pv-record-dialog-open');
                    }, PV_DIALOG_LEAVE_MS);
                },
            });
        });

        function registerVelmWireConfirm() {
            if (window.__velmWireConfirmRegistered) {
                return;
            }

            if (typeof Livewire === 'undefined' || typeof Livewire.directive !== 'function') {
                return;
            }

            window.__velmWireConfirmRegistered = true;

            Livewire.directive('confirm', ({ el, directive, cleanup }) => {
                let message = directive.expression || '';
                let promptExpected = '';

                if (directive.modifiers.includes('prompt') && message.includes('|')) {
                    const pipeIndex = message.lastIndexOf('|');
                    promptExpected = message.slice(pipeIndex + 1);
                    message = message.slice(0, pipeIndex);
                }

                const onClick = async (event) => {
                    event.preventDefault();
                    event.stopImmediatePropagation();

                    const ok = await window.pvConfirm(message, {
                        title: el.getAttribute('data-pv-confirm-title') || @js(__('Confirm')),
                        confirmLabel: @js(__('Continue')),
                        variant: el.getAttribute('data-pv-confirm-variant') || 'primary',
                        promptExpected,
                    });

                    if (!ok) {
                        return;
                    }

                    el.removeEventListener('click', onClick, { capture: true });
                    el.click();
                    el.addEventListener('click', onClick, { capture: true });
                };

                el.addEventListener('click', onClick, { capture: true });

                cleanup(() => {
                    el.removeEventListener('click', onClick, { capture: true });
                });
            });
        }

        document.addEventListener('livewire:init', registerVelmWireConfirm, { once: true });
        registerVelmWireConfirm();
    })();
</script>
