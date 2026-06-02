<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

final class ManifestPatcher
{
    public static function appendModel(string $manifestPath, string $fqn, string $shortClass): void
    {
        $text = self::read($manifestPath);

        if (! str_contains($text, "use {$fqn};")) {
            $text = preg_replace(
                '/(declare\(strict_types=1\);\s*\n\s*\n)/',
                "$1use {$fqn};\n",
                $text,
                1,
            ) ?? $text;
        }

        if (! str_contains($text, "{$shortClass}::class")) {
            if (preg_match('/->models\s*\(\s*\n/', $text) === 1) {
                $text = preg_replace(
                    '/(->models\s*\(\s*\n(?:.*\n)*?)(\s*\))/s',
                    "$1        {$shortClass}::class,\n$2",
                    $text,
                    1,
                ) ?? $text;
            } elseif (preg_match('/->models\(([^)]*)\)/', $text, $matches) === 1) {
                $inner = trim($matches[1]);
                $replacement = $inner === ''
                    ? "->models({$shortClass}::class)"
                    : "->models({$inner}, {$shortClass}::class)";
                $text = preg_replace('/->models\([^)]*\)/', $replacement, $text, 1) ?? $text;
            } else {
                $text = preg_replace(
                    '/(->depends\([^)]+\))/',
                    "$1\n    ->models({$shortClass}::class)",
                    $text,
                    1,
                ) ?? $text;
            }
        }

        self::write($manifestPath, $text);
    }

    public static function appendData(string $manifestPath, string $entry): void
    {
        $text = self::read($manifestPath);
        $quoted = "'".addslashes($entry)."'";

        if (str_contains($text, $quoted)) {
            return;
        }

        if (preg_match('/->data\(([^)]*)\)/', $text, $matches) === 1) {
            $inner = trim($matches[1]);
            $replacement = $inner === ''
                ? "->data({$quoted})"
                : "->data({$inner}, {$quoted})";
            $text = preg_replace('/->data\([^)]*\)/', $replacement, $text, 1) ?? $text;
        } else {
            $text = preg_replace(
                '/(->depends\([^)]+\))/',
                "$1\n    ->data({$quoted})",
                $text,
                1,
            ) ?? $text;
        }

        self::write($manifestPath, $text);
    }

    private static function read(string $manifestPath): string
    {
        if (! is_file($manifestPath)) {
            throw new \RuntimeException("Manifest not found: {$manifestPath}");
        }

        $text = file_get_contents($manifestPath);

        if ($text === false) {
            throw new \RuntimeException("Could not read manifest: {$manifestPath}");
        }

        return $text;
    }

    private static function write(string $manifestPath, string $text): void
    {
        file_put_contents($manifestPath, $text);
    }
}
