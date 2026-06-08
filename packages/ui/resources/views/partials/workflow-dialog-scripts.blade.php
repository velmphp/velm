<script>
    document.addEventListener('alpine:init', () => {
        const PV_DIALOG_LEAVE_MS = 220;

        Alpine.store('workflowDialog', {
            isOpen: false,
            title: '',
            posX: null,
            posY: null,
            _dragging: false,
            _dragOffsetX: 0,
            _dragOffsetY: 0,
            _onResult: null,
            _error: '',

            panelStyle() {
                if (this.posX === null || this.posY === null) {
                    return '';
                }

                return `left:${this.posX}px;top:${this.posY}px;right:auto;bottom:auto;transform:none;margin:0;`;
            },

            startDrag(event) {
                if (event.button !== 0) return;
                const panel = event.currentTarget.closest('.pv-record-dialog-panel');
                if (!panel) return;
                const rect = panel.getBoundingClientRect();
                if (this.posX === null) {
                    this.posX = rect.left;
                    this.posY = rect.top;
                }
                this._dragging = true;
                this._dragOffsetX = event.clientX - this.posX;
                this._dragOffsetY = event.clientY - this.posY;
                const onMove = (e) => {
                    if (!this._dragging) return;
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

            open(title, html, onResult) {
                this.title = title || '{{ __('Workflow') }}';
                this._onResult = typeof onResult === 'function' ? onResult : null;
                this._error = '';
                const body = document.getElementById('pv-workflow-dialog-body');
                if (body) {
                    body.innerHTML = html;
                    body.querySelectorAll('script').forEach((old) => {
                        const s = document.createElement('script');
                        s.textContent = old.textContent;
                        old.replaceWith(s);
                    });
                }
                this.isOpen = true;
                document.body.classList.add('pv-record-dialog-open');
            },

            setError(message) {
                this._error = message;
                const body = document.getElementById('pv-workflow-dialog-body');
                if (!body) return;
                let el = body.querySelector('[data-pv-workflow-dialog-error]');
                if (!el) {
                    el = document.createElement('p');
                    el.dataset.pvWorkflowDialogError = '1';
                    el.className = 'mb-3 text-sm text-fg-danger';
                    body.prepend(el);
                }
                el.textContent = message;
            },

            close(result) {
                const cb = this._onResult;
                this._onResult = null;
                this.isOpen = false;

                window.setTimeout(() => {
                    this.title = '';
                    document.body.classList.remove('pv-record-dialog-open');
                    const body = document.getElementById('pv-workflow-dialog-body');
                    if (body) {
                        body.innerHTML = '';
                    }
                    if (cb) {
                        cb(result);
                    }
                }, PV_DIALOG_LEAVE_MS);
            },
        });

        window.PvWorkflowDialog = {
            openForm(url, title, onResult) {
                fetch(url, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((r) => {
                        if (!r.ok) throw new Error('Could not load form');
                        return r.text();
                    })
                    .then((html) => Alpine.store('workflowDialog').open(title, html, onResult))
                    .catch((e) => window.pvAlert(e.message, { variant: 'danger' }));
            },

            openApproval(approvalId, approved, transitionLabel, onResult) {
                const title = approved
                    ? '{{ __('Approve') }}: ' + transitionLabel
                    : '{{ __('Reject') }}: ' + transitionLabel;
                const html = `
                    <form class="space-y-4" id="pv-wf-approval-form">
                        <h3 class="text-base font-semibold text-heading">${title}</h3>
                        <label class="block text-sm">
                            <span class="font-medium text-heading">{{ __('Comment') }}</span>
                            <textarea name="comment" rows="3" class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm"></textarea>
                        </label>
                        <p class="text-sm text-fg-danger hidden" id="pv-wf-approval-error"></p>
                        <div class="flex justify-end gap-2 border-t border-default pt-3">
                            <button type="button" class="pv-btn pv-btn-secondary" id="pv-wf-approval-cancel">{{ __('Cancel') }}</button>
                            <button type="submit" class="pv-btn pv-btn-primary">{{ __('Continue') }}</button>
                        </div>
                    </form>`;
                Alpine.store('workflowDialog').open(title, html, onResult);
                setTimeout(() => {
                    const form = document.getElementById('pv-wf-approval-form');
                    const err = document.getElementById('pv-wf-approval-error');
                    document.getElementById('pv-wf-approval-cancel')?.addEventListener('click', () => window.PvWorkflowDialog.close());
                    form?.addEventListener('submit', async (e) => {
                        e.preventDefault();

                        await window.pvWithActionBusy(async () => {
                            const comment = form.querySelector('[name=comment]')?.value || '';
                            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                            const r = await fetch(`/web/workflow/approvals/${approvalId}/act`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ approved, comment }),
                            });
                            const data = await r.json().catch(() => ({}));
                            if (!r.ok) {
                                if (err) { err.textContent = data.message || 'Failed'; err.classList.remove('hidden'); }
                                return;
                            }
                            window.PvWorkflowDialog.close(data);
                        }, { message: @js(__('Working…')) });
                    });
                }, 0);
            },

            close(result) {
                Alpine.store('workflowDialog').close(result);
            },

            setError(message) {
                Alpine.store('workflowDialog').setError(message);
            },
        };
    });
</script>
