@props([
    'livewire' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('velm-ui::partials.document-head-auth', ['livewire' => $livewire])
    </head>
    <body class="flex min-h-screen flex-col bg-neutral-secondary font-sans text-body antialiased">
        <div class="flex flex-1 items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
        </div>

        @include('velm-ui::partials.document-foot-auth')
    </body>
</html>
