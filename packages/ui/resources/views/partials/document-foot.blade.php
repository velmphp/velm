@php
    $livewire ??= null;
    $usesLivewire = $livewire !== null && $livewire instanceof \Livewire\Component;
@endphp

@include('velm-ui::partials.form-scripts')
@include('velm-ui::partials.flash-notify')
@include('velm-ui::partials.velm-nav-scripts')

@stack('before-livewire')

@if ($usesLivewire)
    <script src="{{ \Velm\Ui\UiAssets::fileHelpersScriptHref() }}" data-navigate-track></script>
    @livewireScripts
@endif

<script src="{{ \Velm\Ui\UiAssets::flowbiteScriptHref() }}" defer @if ($usesLivewire) data-navigate-track @endif></script>

@stack('scripts')
