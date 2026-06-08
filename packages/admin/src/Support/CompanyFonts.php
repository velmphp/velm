<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Modules\Base\CompanyUiChoices;

/**
 * Per-company Google Fonts typography (PyVelm-compatible).
 */
final class CompanyFonts
{
    public const string DEFAULT_FAMILY = 'Inter';

    /** @var list<int> */
    private const WEIGHTS = [300, 400, 500, 600, 700];

    public static function normalize(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $text = trim($name);

        if ($text === '' || in_array(strtolower($text), ['default', 'inter'], true)) {
            return '';
        }

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9 \-&]{0,78}[A-Za-z0-9]?$/', $text)) {
            return '';
        }

        return $text;
    }

    public static function stylesheetUrl(string $family): string
    {
        $name = self::normalize($family);

        if ($name === '') {
            $name = self::DEFAULT_FAMILY;
        }

        $familyParam = str_replace(' ', '+', $name);
        $weights = implode(';', array_map(strval(...), self::WEIGHTS));

        return "https://fonts.googleapis.com/css2?family={$familyParam}:wght@{$weights}&display=swap";
    }

    public static function css(?string $family): string
    {
        $name = self::normalize($family);

        if ($name === '') {
            return '';
        }

        $stack = "'{$name}', ui-sans-serif, system-ui, sans-serif";

        return implode("\n", [
            '/* velm company font — generated from res.company.font_family */',
            '@layer theme {',
            '  :root, :host {',
            "    --font-sans: {$stack};",
            "    --font-body: {$stack};",
            "    --default-font-family: {$stack};",
            '  }',
            '}',
            'html, body {',
            "  font-family: {$stack};",
            '}',
        ]);
    }

    public static function resolve(?string $companyValue): string
    {
        $fromCompany = self::normalize($companyValue);

        if ($fromCompany !== '') {
            return $fromCompany;
        }

        $fromEnv = self::normalize((string) config('velm.font_family', env('VELM_FONT_FAMILY', '')));

        return $fromEnv !== '' ? $fromEnv : self::DEFAULT_FAMILY;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     company_font_family: string,
     *     company_font_stylesheet_url: string,
     *     company_font_style: string
     * }
     */
    public static function contextFromCompanyRow(array $row): array
    {
        $companyValue = isset($row['font_family']) ? (string) $row['font_family'] : null;
        $family = self::resolve($companyValue);
        $custom = self::normalize($companyValue) !== '' || self::normalize((string) config('velm.font_family', env('VELM_FONT_FAMILY', ''))) !== '';

        return [
            'company_font_family' => $family,
            'company_font_stylesheet_url' => self::stylesheetUrl($family),
            'company_font_style' => $custom ? self::css($family) : '',
        ];
    }

    public static function isAllowedChoice(?string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return true;
        }

        return in_array($normalized, CompanyUiChoices::FONT_FAMILIES, true);
    }
}
