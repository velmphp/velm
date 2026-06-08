<?php

declare(strict_types=1);

return [
    'addon_paths' => [
        base_path('vendor/velmphp/modules/modules'),
        base_path('addons'),
    ],

    /** Roots scanned for {@code Addons\…} PHP classes (no composer.json PSR-4 per module). */
    'addon_autoload_paths' => array_values(array_filter([
        base_path('addons'),
        ...array_filter(array_map('trim', explode(',', (string) env('VELM_ADDON_PATHS', '')))),
    ])),
    'bootstrap_modules' => ['base', 'admin', 'geo_data', 'file_manager'],

    /** List pagination: "simple" (prev/next) or "full" (page numbers + result count). */
    'list_pagination' => env('VELM_LIST_PAGINATION', 'simple'),

    /** Default list/kanban page size (must be one of list_page_sizes, or 0 for all). */
    'list_page_size' => (int) env('VELM_LIST_PAGE_SIZE', 10),

    /** Allowed page-size choices (comma-separated integers; "All" is always offered). */
    'list_page_sizes' => array_values(array_filter(array_map(
        static fn (string $size): int => max(1, (int) trim($size)),
        explode(',', (string) env('VELM_LIST_PAGE_SIZES', '10,25,50,100')),
    ))),

    /** ISO-3166 alpha-2 override for geo bootstrap seeding (defaults to outbound IP geolocation). */
    'geo_country' => env('VELM_GEO_COUNTRY'),

    /** ISO-4217 override for company currency; when unset, uses the bootstrap country's currency. */
    'default_currency' => env('VELM_DEFAULT_CURRENCY'),

    'views' => [
        /** Skip inherit ops whose target node was removed by an earlier patch (third-party safe). */
        'skip_missing_inherit_targets' => env('VELM_VIEWS_SKIP_MISSING_INHERIT_TARGETS', true),
    ],

    /** Shell navigation: "apps" (rail + top bar) or "sidebar" (nested column). */
    'menu_layout' => env('VELM_MENU_LAYOUT', 'apps'),

    /** Default Google Font when company font_family is empty (Inter when unset). */
    'font_family' => env('VELM_FONT_FAMILY'),

    'branding' => [
        'VELM_APP_NAME' => env('VELM_APP_NAME'),
        'VELM_APP_TAGLINE' => env('VELM_APP_TAGLINE'),
        'VELM_LOGO_URL' => env('VELM_LOGO_URL'),
        'VELM_LOGO_URL_DARK' => env('VELM_LOGO_URL_DARK'),
        'VELM_HEADER_LOGO_HEIGHT' => env('VELM_HEADER_LOGO_HEIGHT'),
        'VELM_SHOW_HEADER_BRAND_TEXT' => env('VELM_SHOW_HEADER_BRAND_TEXT'),
        'VELM_FAVICON_URL' => env('VELM_FAVICON_URL'),
        'VELM_COPYRIGHT' => env('VELM_COPYRIGHT'),
        'VELM_SUPPORT_EMAIL' => env('VELM_SUPPORT_EMAIL'),
        'VELM_SUPPORT_URL' => env('VELM_SUPPORT_URL'),
        'VELM_SHOW_POWERED_BY' => env('VELM_SHOW_POWERED_BY'),
    ],

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

    /**
     * IT audit log storage and retention.
     *
     * - dsn: optional database URL for a dedicated audit connection (empty = main app DB)
     * - retention_days: purge audit/login/lifecycle rows older than this (daily cron)
     */
    'audit' => [
        'dsn' => env('VELM_AUDIT_DSN'),
        'retention_days' => (int) env('VELM_AUDIT_RETENTION_DAYS', 90),
    ],
];
