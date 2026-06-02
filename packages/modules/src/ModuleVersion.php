<?php

declare(strict_types=1);

namespace Velm\Modules;

final class ModuleVersion
{
    /**
     * @return list<int>
     */
    public static function parse(string $version): array
    {
        if ($version === '') {
            return [];
        }

        return array_map(intval(...), explode('.', $version));
    }

    /**
     * @param  list<int>  $version
     * @return list<int>
     */
    public static function pad(array $version, int $width): array
    {
        $padded = array_values($version);

        while (count($padded) < $width) {
            $padded[] = 0;
        }

        return $padded;
    }

    /**
     * @param  list<int>  $left
     * @param  list<int>  $right
     */
    public static function compare(array $left, array $right): int
    {
        $width = max(count($left), count($right), 1);
        $a = self::pad($left, $width);
        $b = self::pad($right, $width);

        foreach ($a as $index => $segment) {
            if ($segment < $b[$index]) {
                return -1;
            }

            if ($segment > $b[$index]) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * @param  list<int>  $installed
     * @param  list<int>  $target
     */
    public static function needsUpgrade(array $installed, array $target): bool
    {
        return self::compare($installed, $target) < 0;
    }

    public static function migrationStem(array $from, array $to): string
    {
        return implode('_', array_map('strval', $from)).'_to_'.implode('_', array_map('strval', $to));
    }

    /**
     * @return array{0: list<int>, 1: list<int>}|null
     */
    public static function parseMigrationFilename(string $stem): ?array
    {
        if (! str_contains($stem, '_to_')) {
            return null;
        }

        [$fromPart, $toPart] = explode('_to_', $stem, 2);

        if ($fromPart === '' || $toPart === '') {
            return null;
        }

        return [
            array_map(intval(...), explode('_', $fromPart)),
            array_map(intval(...), explode('_', $toPart)),
        ];
    }
}
