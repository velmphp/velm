{{-- Deprecated: use velm-ui::layouts.shell with ?embed=1 --}}
@props([
    'livewire' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('velm-ui::partials.document-head', ['livewire' => $livewire])
    </head>
    <body class="min-h-screen bg-neutral-primary font-sans text-body antialiased">
        <main class="px-4 py-4 md:px-5">
            {{ $slot }}
        </main>

        @include('velm-ui::partials.confirm-dialog')
        @include('velm-ui::partials.document-foot', ['livewire' => $livewire])
        @include('velm-ui::partials.confirm-dialog-scripts')
    </body>
</html>
