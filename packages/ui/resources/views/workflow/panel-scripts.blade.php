@once
    @push('scripts')
        <script>
            function pvWorkflowBar() {
                return {
                    loading: true,
                    ctx: null,
                    error: '',
                    resModel: '',
                    resId: 0,
                    load() {
                        const el = this.$root;
                        this.resModel = el.dataset.resModel || '';
                        this.resId = parseInt(el.dataset.resId || '0', 10);
                        fetch(`/web/workflow/context?res_model=${encodeURIComponent(this.resModel)}&res_id=${this.resId}`, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then((r) => r.json())
                            .then((data) => { this.ctx = data.has_workflow === false ? null : data; this.loading = false; })
                            .catch(() => { this.loading = false; });
                    },
                    refreshContext(data) {
                        if (data?.context) {
                            this.ctx = data.context;
                        } else {
                            this.load();
                        }
                        this.error = '';
                        window.dispatchEvent(new CustomEvent('velm-workflow-updated'));
                    },
                    start() {
                        this.post('/web/workflow/start', { res_model: this.resModel, res_id: this.resId });
                    },
                    runTransition(tr) {
                        if (!this.ctx?.instance_id) return;
                        const fields = tr.form_fields || [];
                        const url = `/web/workflow/instances/${this.ctx.instance_id}/transition/${tr.key}/form`;
                        if (fields.length && window.PvWorkflowDialog) {
                            window.PvWorkflowDialog.openForm(url, tr.form_title || tr.label, (data) => this.refreshContext(data));
                            return;
                        }
                        this.post(`/web/workflow/instances/${this.ctx.instance_id}/transition/${tr.key}`, { values: {} });
                    },
                    act(approvalId, approved, transitionLabel) {
                        if (window.PvWorkflowDialog) {
                            window.PvWorkflowDialog.openApproval(approvalId, approved, transitionLabel, (data) => this.refreshContext(data));
                            return;
                        }
                        this.post(`/web/workflow/approvals/${approvalId}/act`, { approved, comment: '' });
                    },
                    post(url, body) {
                        this.error = '';
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(body),
                        })
                            .then(async (r) => {
                                const data = await r.json();
                                if (!r.ok) throw new Error(data.message || 'Request failed');
                                this.refreshContext(data);
                            })
                            .catch((e) => { this.error = e.message || String(e); });
                    },
                };
            }
        </script>
    @endpush
@endonce
