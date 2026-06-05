/**
 * Breadcrumbs and Back use the server-built trail on [data-velm-breadcrumb-trail]
 * (Home → List → Detail → Edit). No session/history stack.
 */
(function () {
    function normalizeUrl(url) {
        try {
            const u = new URL(url, window.location.origin);

            return u.pathname + u.search;
        } catch {
            return String(url);
        }
    }

    function readTrail() {
        const el = document.querySelector('[data-velm-breadcrumb-trail]');

        if (! el) {
            return [];
        }

        try {
            const parsed = JSON.parse(el.getAttribute('data-velm-breadcrumb-trail') || '[]');

            if (! Array.isArray(parsed)) {
                return [];
            }

            return parsed
                .filter((crumb) => crumb && typeof crumb.label === 'string' && crumb.label.trim() !== '')
                .map((crumb) => ({
                    label: crumb.label.trim(),
                    url: typeof crumb.url === 'string' && crumb.url !== '' ? normalizeUrl(crumb.url) : null,
                }));
        } catch {
            return [];
        }
    }

    function backUrl(fallback) {
        const trail = readTrail();

        for (let i = trail.length - 2; i >= 0; i -= 1) {
            if (trail[i].url) {
                return trail[i].url;
            }
        }

        if (fallback) {
            return normalizeUrl(fallback);
        }

        if (trail[0]?.url) {
            return trail[0].url;
        }

        const panel = document.querySelector('meta[name="velm-panel-path"]')?.content?.trim() || 'velm';

        return `/${panel}/dashboard`;
    }

    function navigate(url) {
        const target = normalizeUrl(url);

        if (window.Livewire && typeof window.Livewire.navigate === 'function') {
            window.Livewire.navigate(target);

            return;
        }

        window.location.href = target;
    }

    function navigateToIndex(index) {
        const trail = readTrail();
        const crumb = trail[index];

        if (! crumb?.url) {
            return;
        }

        navigate(crumb.url);
    }

    function goBack(fallback) {
        navigate(backUrl(fallback));
    }

    function syncDocumentTitle() {
        const root = document.querySelector('[data-velm-nav-label]');
        const label = (root?.getAttribute('data-velm-nav-label') || '').trim();
        const app =
            document.querySelector('meta[name="velm-app-name"]')?.content?.trim() || 'Velm';
        const titleEl = document.getElementById('velm-document-title');
        const nextTitle = label ? `${label} — ${app}` : app;

        if (titleEl) {
            titleEl.textContent = nextTitle;
        } else {
            document.title = nextTitle;
        }

        const meta = document.querySelector('meta[name="velm-nav-label"]');

        if (meta) {
            if (label) {
                meta.setAttribute('content', label);
            } else {
                meta.removeAttribute('content');
            }
        }
    }

    function dispatchChanged() {
        syncDocumentTitle();
        document.dispatchEvent(new CustomEvent('velm:nav-changed'));
    }

    document.addEventListener('livewire:navigated', () => {
        requestAnimationFrame(() => requestAnimationFrame(dispatchChanged));
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncDocumentTitle);
    } else {
        syncDocumentTitle();
    }

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('velmBreadcrumbs', () => ({
            items: [],
            init() {
                this.refresh();
                document.addEventListener('velm:nav-changed', () => this.refresh());
            },
            refresh() {
                this.items = readTrail();
            },
            go(index) {
                navigateToIndex(index);
            },
        }));
    });

    window.VelmNav = {
        backUrl,
        readTrail,
        navigate,
        navigateToIndex,
        goBack,
    };
})();
