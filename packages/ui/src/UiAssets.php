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

    public static function workflowBuilderScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-workflow-builder.js', 'resources/js/pv-workflow-builder.js');
    }

    public static function richTextScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/pv-rich-text.js',
            'Missing packages/ui/resources/js/pv-rich-text.js. Run: cd packages/ui && npm install && npm run build',
        );
    }

    public static function codeEditorScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/pv-code-editor.js',
            'Missing packages/ui/resources/js/pv-code-editor.js. Run: cd packages/ui && npm install && npm run build',
        );
    }

    public static function richTextScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-rich-text.js', 'resources/js/pv-rich-text.js');
    }

    public static function codeEditorScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-code-editor.js', 'resources/js/pv-code-editor.js');
    }

    public static function graphScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/pv-graph.js',
            'Missing packages/ui/resources/js/pv-graph.js. Run: cd packages/ui && npm install && npm run build',
        );
    }

    public static function pivotScriptPath(): string
    {
        return self::requireBuiltFile(
            dirname(__DIR__).'/resources/js/pv-pivot.js',
            'Missing packages/ui/resources/js/pv-pivot.js. Run: cd packages/ui && npm install && npm run build',
        );
    }

    public static function graphScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-graph.js', 'resources/js/pv-graph.js');
    }

    public static function pivotScriptHref(): string
    {
        return self::publishedOrVendor('js/velm/pv-pivot.js', 'resources/js/pv-pivot.js');
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
