<?php

declare(strict_types=1);

return [
    'addon_paths' => [
        base_path('vendor/velmphp/modules/modules'),
        base_path('addons'),
    ],
    'bootstrap_modules' => ['base', 'admin'],

    /** Shell navigation: "apps" (rail + top bar) or "sidebar" (nested column). */
    'menu_layout' => env('VELM_MENU_LAYOUT', 'apps'),

    /** URL prefix for the Velm admin panel (Livewire shell). */
    'panel_path' => env('VELM_PANEL_PATH', 'velm'),
];
