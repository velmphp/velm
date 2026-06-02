<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

/**
 * Custom accent palette for Filament {@see \Velm\Filament\VelmPanelProvider} (warning slot).
 *
 * Primary uses Filament {@see \Filament\Support\Colors\Color::Amber}; shell CSS uses the
 * matching Tailwind amber scale in {@see ../resources/css/velm-palette.css}.
 */
final class VelmBrandColors
{
    /**
     * Registered as Filament "warning" (Velm accent orange).
     *
     * @return array<int, string>
     */
    public static function accent(): array
    {
        return [
            50 => '#fef0eb',
            100 => '#fde0d4',
            200 => '#fbc1a9',
            300 => '#f9a27e',
            400 => '#f67a4c',
            500 => '#f1511b',
            600 => '#cd4416',
            700 => '#a93712',
            800 => '#852b0e',
            900 => '#611f0a',
            950 => '#3d1306',
        ];
    }
}
