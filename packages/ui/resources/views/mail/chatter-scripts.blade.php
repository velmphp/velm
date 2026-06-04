@once
    @push('scripts')
        <script>
            function pvMailChatter() {
                return {
                    loading: true,
                    ctx: null,
                    error: '',
                    draft: '',
                    posting: false,
                    resModel: '',
                    resId: 0,
                    load() {
                        const el = this.$root;
                        this.resModel = el.dataset.resModel || '';
                        this.resId = parseInt(el.dataset.resId || '0', 10);
                        if (!this.resModel || !this.resId) {
                            this.loading = false;
                            return;
                        }
                        fetch(
                            `/web/mail/thread?res_model=${encodeURIComponent(this.resModel)}&res_id=${this.resId}`,
                            { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } },
                        )
                            .then((r) => r.json())
                            .then((data) => {
                                this.ctx = data.has_thread === false ? null : data;
                                this.loading = false;
                            })
                            .catch(() => {
                                this.loading = false;
                            });
                    },
                    refreshThread(data) {
                        if (data?.thread) {
                            this.ctx = data.thread;
                        } else {
                            this.load();
                        }
                        this.error = '';
                    },
                    postMessage() {
                        const body = (this.draft || '').trim();
                        if (!body || this.posting || !this.ctx?.can_post) {
                            return;
                        }
                        this.posting = true;
                        this.error = '';
                        this.post('/web/mail/messages', {
                            res_model: this.resModel,
                            res_id: this.resId,
                            body,
                        })
                            .then((data) => {
                                this.draft = '';
                                this.refreshThread(data);
                            })
                            .finally(() => {
                                this.posting = false;
                            });
                    },
                    toggleFollow() {
                        if (!this.ctx || this.ctx.readonly) {
                            return;
                        }
                        this.post('/web/mail/follow', {
                            res_model: this.resModel,
                            res_id: this.resId,
                            follow: !this.ctx.following,
                        });
                    },
                    post(url, body) {
                        this.error = '';
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        return fetch(url, {
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
                                if (!r.ok) {
                                    throw new Error(data.message || 'Request failed');
                                }
                                return data;
                            })
                            .catch((e) => {
                                this.error = e.message || String(e);
                                throw e;
                            });
                    },
                };
            }
        </script>
    @endpush
@endonce
