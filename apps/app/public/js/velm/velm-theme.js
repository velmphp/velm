/**
 * Dark mode for the Velm shell — survives Livewire wire:navigate (html morph strips .dark).
 */
(function () {
    if (window.__velmThemeBootstrapped) {
        window.VelmTheme?.apply?.();

        return;
    }

    window.__velmThemeBootstrapped = true;

    const defaultMode =
        document.documentElement.dataset.velmThemeDefault ||
        window.VelmTheme?.defaultMode ||
        'system';

    function preference() {
        return localStorage.getItem('theme') ?? defaultMode;
    }

    function shouldBeDark() {
        const stored = preference();

        return (
            stored === 'dark' ||
            (stored === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)
        );
    }

    function apply() {
        const isDark = shouldBeDark();

        document.documentElement.classList.toggle('dark', isDark);
        window.theme = preference();
        document.dispatchEvent(
            new CustomEvent('velm:theme-changed', { detail: { isDark } }),
        );
    }

    function set(mode) {
        localStorage.setItem('theme', mode);
        apply();
    }

    function toggle() {
        set(shouldBeDark() ? 'light' : 'dark');
    }

    window.VelmTheme = {
        defaultMode,
        preference,
        shouldBeDark,
        apply,
        set,
        toggle,
    };

    apply();

    document.addEventListener('livewire:navigating', () => {
        queueMicrotask(apply);
    });

    document.addEventListener('livewire:navigated', () => {
        apply();
        requestAnimationFrame(apply);
    });

    new MutationObserver(() => {
        const wantDark = shouldBeDark();
        const hasDark = document.documentElement.classList.contains('dark');

        if (wantDark !== hasDark) {
            apply();
        }
    }).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
})();
