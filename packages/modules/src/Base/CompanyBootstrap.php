<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

use Velm\Environment;

/**
 * Resolves bootstrap defaults for the default company (country, currency).
 */
final class CompanyBootstrap
{
    public static function configuredDefaultCurrencyCode(): ?string
    {
        $fromEnv = getenv('VELM_DEFAULT_CURRENCY');

        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return strtoupper(trim($fromEnv));
        }

        if (function_exists('config')) {
            $fromConfig = config('velm.default_currency');

            if (is_string($fromConfig) && trim($fromConfig) !== '') {
                return strtoupper(trim($fromConfig));
            }
        }

        return null;
    }

    public static function usesEnvDefaultCurrency(): bool
    {
        return self::configuredDefaultCurrencyCode() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function bootstrapCountry(Environment $env): ?array
    {
        if (! $env->registry->has('res.country')) {
            return null;
        }

        $country = $env->model('res.country')->search([], limit: 1)->read(['id', 'code', 'currency_id'])[0] ?? null;

        return is_array($country) ? $country : null;
    }

    public static function resolveDefaultCurrencyId(Environment $env): ?int
    {
        $configuredCode = self::configuredDefaultCurrencyCode();

        if ($configuredCode !== null) {
            return self::currencyIdForCode($env, $configuredCode);
        }

        $country = self::bootstrapCountry($env);
        $countryCurrencyId = $country['currency_id'] ?? null;

        if ($countryCurrencyId !== null && $countryCurrencyId !== false) {
            return (int) $countryCurrencyId;
        }

        $eur = self::currencyIdForCode($env, 'EUR');

        if ($eur !== null) {
            return $eur;
        }

        $fallback = $env->registry->has('res.currency')
            ? $env->model('res.currency')->search([['active', '=', true]], limit: 1)
            : null;

        return $fallback !== null && $fallback->count() > 0 ? $fallback->ids()[0] : null;
    }

    public static function currencyIdForCode(Environment $env, string $code): ?int
    {
        if (! $env->registry->has('res.currency')) {
            return null;
        }

        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $match = $env->model('res.currency')->search([['name', '=', $code]], limit: 1);

        return $match->count() > 0 ? $match->ids()[0] : null;
    }
}
