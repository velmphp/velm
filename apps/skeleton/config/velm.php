<?php

declare(strict_types=1);

$monorepoModules = realpath(base_path('../../packages/modules/modules')) ?: null;
$vendorModules = base_path('vendor/velmphp/modules/modules');

$addonPaths = [];

if ($monorepoModules !== false && is_dir($monorepoModules)) {
    $addonPaths[] = $monorepoModules;
} elseif (is_dir($vendorModules)) {
    $addonPaths[] = $vendorModules;
}

$addonPaths[] = base_path('addons');

return [
    'addon_paths' => $addonPaths,

    'bootstrap_modules' => ['base', 'admin'],

    'menu_layout' => env('VELM_MENU_LAYOUT', 'apps'),
];
