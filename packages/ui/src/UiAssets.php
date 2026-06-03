<?php

declare(strict_types=1);

namespace Velm\Ui;

final class UiAssets
{
    public static function stylesheetPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/css/velm.css',
            'Missing packages/ui/resources/css/velm.css. Run: cd packages/ui && npm install && npm run build:css',
        );
    }

    public static function flowbiteScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/flowbite.min.js',
            'Missing packages/ui/resources/js/flowbite.min.js. Run: cd packages/ui && npm install && npm run build:css',
        );
    }

    public static function navigationScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/velm-nav.js',
            'Missing packages/ui/resources/js/velm-nav.js.',
        );
    }

    public static function stylesheetHref(): string
    {
        $published = public_path('css/velm/velm.css');

        if (is_file($published)) {
            return asset('css/velm/velm.css');
        }

        return asset('vendor/velm-ui/velm.css');
    }

    public static function flowbiteScriptHref(): string
    {
        $published = public_path('js/velm/flowbite.min.js');

        if (is_file($published)) {
            return asset('js/velm/flowbite.min.js');
        }

        return asset('vendor/velm-ui/flowbite.min.js');
    }

    public static function navigationScriptHref(): string
    {
        $published = public_path('js/velm/velm-nav.js');

        if (is_file($published)) {
            return asset('js/velm/velm-nav.js');
        }

        return asset('vendor/velm-ui/velm-nav.js');
    }

    private static function requireBuiltFile(string $path, string $message): string
    {
        if (! is_file($path)) {
            throw new \RuntimeException($message);
        }

        return $path;
    }
}
