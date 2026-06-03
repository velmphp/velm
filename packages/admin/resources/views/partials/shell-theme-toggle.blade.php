@if (\Velm\Admin\Support\VelmPanel::hasDarkMode() && ! \Velm\Admin\Support\VelmPanel::hasDarkModeForced())
    <button
        type="button"
        class="velm-theme-toggle"
        aria-label="{{ __('Toggle dark mode') }}"
        :aria-pressed="isDark.toString()"
        @click="toggleTheme()"
    >
        <span class="velm-theme-toggle__track">
            <span class="velm-theme-toggle__thumb" :class="{ 'velm-theme-toggle__thumb--dark': isDark }">
                <svg
                    class="velm-theme-toggle__icon velm-theme-toggle__icon--sun"
                    :class="{ 'velm-theme-toggle__icon--active': ! isDark }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"
                    />
                </svg>
                <svg
                    class="velm-theme-toggle__icon velm-theme-toggle__icon--moon"
                    :class="{ 'velm-theme-toggle__icon--active': isDark }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                    />
                </svg>
            </span>
        </span>
    </button>
@endif
