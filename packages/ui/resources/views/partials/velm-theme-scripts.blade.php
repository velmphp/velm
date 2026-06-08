@if (\Velm\Admin\Support\VelmPanel::hasDarkMode() && ! \Velm\Admin\Support\VelmPanel::hasDarkModeForced())
    <script>
        document.documentElement.dataset.velmThemeDefault = @js(\Velm\Admin\Support\VelmPanel::getDefaultThemeMode());
    </script>
    <script src="{{ \Velm\Ui\UiAssets::themeScriptHref() }}" data-navigate-once></script>
@endif
