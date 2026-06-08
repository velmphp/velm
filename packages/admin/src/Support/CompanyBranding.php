<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Environment;

final class CompanyBranding
{
    /** Matches shell topbar `min-h-[60px]`. */
    private const int DEFAULT_HEADER_LOGO_HEIGHT = 60;

    /**
     * @return array<string, mixed>
     */
    public static function forEnvironment(Environment $env): array
    {
        $companyId = $env->companyId();
        $row = self::loadCompanyRow($env, $companyId);
        $primary = CompanyTheme::normalizeHex(isset($row['primary_color']) ? (string) $row['primary_color'] : null) ?? '';

        return array_merge(
            self::brandingFromRow($row, $primary),
            CompanyFonts::contextFromCompanyRow($row),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function brandingFromRow(array $row, string $primary): array
    {
        $logoLight = self::pickString($row['logo_url'] ?? null, 'VELM_LOGO_URL');
        $logoDark = self::pickString($row['logo_url_dark'] ?? null, 'VELM_LOGO_URL_DARK') ?: $logoLight;
        $headerHeight = self::pickInt($row['header_logo_height'] ?? null, 'VELM_HEADER_LOGO_HEIGHT', self::DEFAULT_HEADER_LOGO_HEIGHT);
        $appName = self::resolveAppName($row);

        return [
            'app_name' => $appName,
            'tagline' => self::pickString($row['app_tagline'] ?? null, 'VELM_APP_TAGLINE', 'Welcome back.'),
            'logo_url_light' => $logoLight,
            'logo_url_dark' => $logoDark,
            'header_logo_height' => $headerHeight,
            'header_logo_style' => "height: {$headerHeight}px; width: auto;",
            'show_header_brand_text' => self::pickBool($row['show_header_brand_text'] ?? null, 'VELM_SHOW_HEADER_BRAND_TEXT', true),
            'favicon_url' => self::pickString($row['favicon_url'] ?? null, 'VELM_FAVICON_URL'),
            'copyright' => self::pickString($row['copyright_text'] ?? null, 'VELM_COPYRIGHT'),
            'support_email' => self::pickString($row['support_email'] ?? null, 'VELM_SUPPORT_EMAIL'),
            'support_url' => self::pickString($row['support_url'] ?? null, 'VELM_SUPPORT_URL'),
            'show_powered_by' => self::pickBool($row['show_powered_by'] ?? null, 'VELM_SHOW_POWERED_BY', true),
            'has_logo' => $logoLight !== '',
            'company_primary_color' => $primary,
            'company_theme_style' => CompanyTheme::css($primary),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadCompanyRow(Environment $env, ?int $companyId): array
    {
        if (! $env->registry->has('res.company')) {
            return [];
        }

        if ($companyId !== null) {
            $rows = $env->withAclBypass(
                fn (): array => $env->model('res.company')->search([['id', '=', $companyId]], limit: 1)->read(),
            );

            return $rows[0] ?? [];
        }

        $rows = $env->withAclBypass(
            fn (): array => $env->model('res.company')->search(limit: 1)->read(),
        );

        return $rows[0] ?? [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function resolveAppName(array $row): string
    {
        $appName = self::pickString($row['app_name'] ?? null, 'VELM_APP_NAME', '');

        if ($appName !== '') {
            return $appName;
        }

        if (isset($row['name']) && is_string($row['name'])) {
            $name = trim($row['name']);

            if ($name !== '') {
                return $name;
            }
        }

        return (string) config('app.name', 'Velm');
    }

    private static function pickString(mixed $companyVal, string $envKey, string $default = ''): string
    {
        if (is_string($companyVal) && trim($companyVal) !== '') {
            return trim($companyVal);
        }

        $fromEnv = trim((string) config("velm.branding.{$envKey}", env($envKey, '')));

        return $fromEnv !== '' ? $fromEnv : $default;
    }

    private static function pickInt(mixed $companyVal, string $envKey, int $default): int
    {
        foreach ([$companyVal, config("velm.branding.{$envKey}", env($envKey))] as $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }

            $n = (int) $raw;
            if ($n > 0) {
                return $n;
            }
        }

        return $default;
    }

    private static function pickBool(mixed $companyVal, string $envKey, bool $default): bool
    {
        if ($companyVal !== null && $companyVal !== '') {
            return filter_var($companyVal, FILTER_VALIDATE_BOOLEAN);
        }

        $raw = strtolower(trim((string) config("velm.branding.{$envKey}", env($envKey, ''))));
        if ($raw === '') {
            return $default;
        }

        return ! in_array($raw, ['0', 'false', 'no', 'off'], true);
    }
}
