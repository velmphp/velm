@include('velm-ui::partials.form-scripts')
@include('velm-ui::partials.flash-notify')

@livewireScripts

<script src="{{ \Velm\Ui\UiAssets::flowbiteScriptHref() }}" defer data-navigate-track></script>

@stack('scripts')
