<?php

declare(strict_types=1);

$frameworkConfigPath = base_path('vendor/velmphp/framework/config/velm.php');

if (! is_file($frameworkConfigPath)) {
    throw new RuntimeException(
        'velmphp/framework is not installed. Run composer install in the application root.',
    );
}

/** @var array<string, mixed> $config */
$config = require $frameworkConfigPath;

return array_replace_recursive($config, [
    'bootstrap_admin' => [
        'email' => env('VELM_ADMIN_EMAIL', 'admin@velm.test'),
        'password' => env('VELM_ADMIN_PASSWORD', 'password'),
    ],

    'menu_layout' => env('VELM_MENU_LAYOUT', 'apps'),

    'attachments' => [
        'disk' => env('VELM_ATTACHMENT_DISK'),
        'backend' => env('VELM_ATTACHMENT_BACKEND'),
        'dir' => env('VELM_ATTACHMENT_DIR'),
    ],

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
]);
