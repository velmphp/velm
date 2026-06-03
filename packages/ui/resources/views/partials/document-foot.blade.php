@include('velm-ui::partials.form-scripts')
@include('velm-ui::partials.flash-notify')
@include('velm-ui::partials.velm-nav-scripts')

@livewireScripts

<script src="{{ \Velm\Ui\UiAssets::flowbiteScriptHref() }}" defer data-navigate-track></script>

@stack('scripts')
