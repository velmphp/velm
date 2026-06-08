<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('velm-ui::partials.document-head', [
            'livewire' => null,
            'pageTitle' => __('Choose file'),
        ])
    </head>
    <body class="min-h-screen bg-neutral-primary font-sans text-body antialiased p-4">
        @include('velm-ui::file-manager.picker', get_defined_vars())

        @include('velm-ui::partials.confirm-dialog')
        @include('velm-ui::file-manager.scripts')
        @livewireScripts
        @include('velm-ui::partials.confirm-dialog-scripts')
    </body>
</html>
