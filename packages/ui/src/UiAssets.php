<?php

declare(strict_types=1);

namespace Velm\Ui;

final class UiAssets
{
    public static function stylesheetPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/css/velm.css',
            'Missing packages/ui/resources/css/velm.css. Run: cd packages/ui && npm install && npm run build',
        );
    }

    public static function flowbiteScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/flowbite.min.js',
            'Missing packages/ui/resources/js/flowbite.min.js. Run: cd packages/ui && npm install && npm run build',
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
        return self::publishedOrVendor('js/velm/velm-nav.js', 'resources/js/velm-nav.js');
    }

    public static function fileHelpersScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-file-helpers.js', 'resources/js/pv-file-helpers.js');
    }

    public static function filesAlpineScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-files-alpine.js', 'resources/js/pv-files-alpine.js');
    }

    public static function fileLibraryScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-file-library.js', 'resources/js/pv-file-library.js');
    }

    public static function fileUrlScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-file-url.js', 'resources/js/pv-file-url.js');
    }

    private static function publishedOrVendor(string $publicRelative, string $packageRelative): string
    {
        $published = public_path($publicRelative);

        if (is_file($published)) {
            return asset($publicRelative);
        }

        return asset('vendor/velm-ui/'.basename($packageRelative));
    }

    private static function requireBuiltFile(string $path, string $message): string
    {
        if (! is_file($path)) {
            throw new \RuntimeException($message);
        }

        return $path;
    }
}
