<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

final class CompanyTheme
{
    /** @var array<int, array{0: float, 1: float}> */
    private const SHADE_STOPS = [
        50 => [0.97, 0.20],
        100 => [0.93, 0.30],
        200 => [0.86, 0.45],
        300 => [0.77, 0.60],
        400 => [0.67, 0.78],
        500 => [0.57, 0.92],
        600 => [0.0, 1.0],
        700 => [0.42, 0.95],
        800 => [0.34, 0.90],
        900 => [0.27, 0.85],
        950 => [0.19, 0.80],
    ];

    public static function normalizeHex(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        $text = trim($color);
        if ($text === '') {
            return null;
        }

        if (! str_starts_with($text, '#')) {
            $text = '#'.$text;
        }

        if (! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $text)) {
            return null;
        }

        if (strlen($text) === 4) {
            $text = '#'.implode('', array_map(static fn (string $ch): string => $ch.$ch, str_split(substr($text, 1))));
        }

        return strtolower($text);
    }

    /**
     * @return array<int, string>
     */
    public static function primaryPalette(string $hexColor): array
    {
        $base = self::normalizeHex($hexColor);
        if ($base === null) {
            return [];
        }

        [$r, $g, $b] = self::hexToRgb($base);
        [$hue, $lightness, $saturation] = self::rgbToHls($r, $g, $b);
        $out = [];

        foreach (self::SHADE_STOPS as $shade => [$targetL, $satMult]) {
            if ($shade === 600) {
                $out[$shade] = $base;

                continue;
            }

            $l = $targetL > 0 ? $targetL : $lightness;
            $s = min(1.0, max(0.0, $saturation * $satMult));
            [$nr, $ng, $nb] = self::hlsToRgb($hue, $l, $s);
            $out[$shade] = self::rgbToHex($nr, $ng, $nb);
        }

        return $out;
    }

    public static function css(?string $hexColor): string
    {
        $palette = self::primaryPalette($hexColor ?? '');
        if ($palette === []) {
            return '';
        }

        $lines = [
            '/* velm company theme */',
            ':root {',
        ];

        foreach ($palette as $shade => $value) {
            $lines[] = "  --color-primary-{$shade}: {$value};";
        }

        $lines = array_merge($lines, [
            '  --color-fg-brand-subtle: var(--color-primary-100);',
            '  --color-fg-brand: var(--color-primary-600);',
            '  --color-fg-brand-strong: var(--color-primary-900);',
            '  --color-brand-softer: var(--color-primary-50);',
            '  --color-brand-soft: var(--color-primary-100);',
            '  --color-brand: var(--color-primary-500);',
            '  --color-brand-medium: var(--color-primary-300);',
            '  --color-brand-strong: var(--color-primary-700);',
            '}',
            '.dark {',
            '  --color-fg-brand: var(--color-primary-500);',
            '  --color-brand-softer: var(--color-primary-950);',
            '  --color-brand-soft: var(--color-primary-900);',
            '}',
        ]);

        return implode("\n", $lines);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function hexToRgb(string $hex): array
    {
        $h = substr($hex, 1);

        return [
            hexdec(substr($h, 0, 2)) / 255,
            hexdec(substr($h, 2, 2)) / 255,
            hexdec(substr($h, 4, 2)) / 255,
        ];
    }

    private static function rgbToHex(float $r, float $g, float $b): string
    {
        return sprintf(
            '#%02x%02x%02x',
            (int) round(max(0, min(255, $r * 255))),
            (int) round(max(0, min(255, $g * 255))),
            (int) round(max(0, min(255, $b * 255))),
        );
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function rgbToHls(float $r, float $g, float $b): array
    {
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, $l, 0.0];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        if ($max === $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }

        return [$h / 6, $l, $s];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function hlsToRgb(float $h, float $l, float $s): array
    {
        if ($s === 0.0) {
            return [$l, $l, $l];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        return [
            self::hueToRgb($p, $q, $h + 1 / 3),
            self::hueToRgb($p, $q, $h),
            self::hueToRgb($p, $q, $h - 1 / 3),
        ];
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }
}
