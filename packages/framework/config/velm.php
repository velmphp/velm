<?php

declare(strict_types=1);

return [
    'addon_paths' => [
        base_path('vendor/velmphp/modules/modules'),
        base_path('addons'),
    ],
    'bootstrap_modules' => ['base', 'admin'],
];
