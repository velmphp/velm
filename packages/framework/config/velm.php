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

    /**
     * Blob storage for ir.attachment.
     *
     * - disk: Laravel filesystem disk name. Unset uses config/filesystems.php "default".
     *   Use "db" for inline bytes in the row.
     * - backend / dir: legacy PyVelm-style overrides (VELM_ATTACHMENT_BACKEND=db|local, VELM_ATTACHMENT_DIR).
     */
    'attachments' => [
        'disk' => env('VELM_ATTACHMENT_DISK'),
        'backend' => env('VELM_ATTACHMENT_BACKEND'),
        'dir' => env('VELM_ATTACHMENT_DIR'),
    ],
];
