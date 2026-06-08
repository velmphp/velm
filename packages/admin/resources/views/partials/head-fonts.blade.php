@php
    $fontUrl = (string) (($velmShell ?? [])['company_font_stylesheet_url'] ?? '');

    if ($fontUrl === '') {
        $fontUrl = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
    }
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="{{ $fontUrl }}" rel="stylesheet" />
