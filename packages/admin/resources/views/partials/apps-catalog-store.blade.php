<script>
    document.addEventListener('alpine:init', () => {
        if (Alpine.store('velmAppsCatalog')) {
            return;
        }

        Alpine.store('velmAppsCatalog', {
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
                    const stateOk = !this.stateFilter || card.dataset.velmAppState === this.stateFilter;
                    const catOk = !this.categoryFilter || card.dataset.velmAppCategory === this.categoryFilter;
                    const queryOk = !q || (card.dataset.velmAppHaystack || '').includes(q);
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
        });
    });
</script>
