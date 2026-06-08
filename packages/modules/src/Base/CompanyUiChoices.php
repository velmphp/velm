<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

/**
 * Curated UI choices for company settings (PyVelm-aligned).
 */
final class CompanyUiChoices
{
    /** @var list<string> */
    public const FONT_FAMILIES = [
        'Roboto',
        'Open Sans',
        'Lato',
        'Montserrat',
        'Source Sans 3',
        'Nunito',
        'Poppins',
        'Raleway',
        'Merriweather',
        'Playfair Display',
        'Oswald',
        'Work Sans',
        'DM Sans',
        'IBM Plex Sans',
    ];

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function fontFamilies(): array
    {
        $choices = [
            ['value' => '', 'label' => 'Default (Inter)'],
        ];

        foreach (self::FONT_FAMILIES as $family) {
            $choices[] = ['value' => $family, 'label' => $family];
        }

        return $choices;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function menuLayouts(): array
    {
        return [
            ['value' => '', 'label' => 'Default (env var)'],
            ['value' => 'apps', 'label' => 'Apps — sidebar icons + top bar'],
            ['value' => 'sidebar', 'label' => 'Sidebar — 3-level collapsible'],
        ];
    }
}
