<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

use Velm\Environment;
use Velm\Modules\Base\CompanyBootstrap;
use Velm\Modules\Base\CurrencyBootstrap;
use Velm\Modules\Base\Seeders\CurrencyReferenceSeeder;

final class GeoCurrencyLinker
{
    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function apply(Environment $env, array $values): array
    {
        $currencyCode = $values['_currency_code'] ?? null;
        unset($values['_currency_code']);

        if (! is_string($currencyCode) || $currencyCode === '' || ! $env->registry->has('res.currency')) {
            return $values;
        }

        $currencyId = CurrencyBootstrap::ensureCode($env, $currencyCode);

        if ($currencyId !== null) {
            $values['currency_id'] = $currencyId;
        }

        return $values;
    }

    /**
     * Links bootstrap companies to the detected country and its currency.
     */
    public static function syncCompanyDefaults(Environment $env): void
    {
        if (! $env->registry->has('res.country') || ! $env->registry->has('res.company')) {
            return;
        }

        $country = CompanyBootstrap::bootstrapCountry($env);

        if ($country === null) {
            return;
        }

        $countryId = (int) ($country['id'] ?? 0);
        $countryCurrencyId = $country['currency_id'] ?? null;

        if ($countryId > 0) {
            $companiesMissingCountry = $env->model('res.company')->search([['country_id', '=', false]]);

            if ($companiesMissingCountry->count() > 0) {
                $companiesMissingCountry->write(['country_id' => $countryId]);
            }
        }

        $currencyId = CompanyBootstrap::resolveDefaultCurrencyId($env);

        if ($currencyId === null) {
            return;
        }

        if (CompanyBootstrap::usesEnvDefaultCurrency()) {
            $companiesMissingCurrency = $env->model('res.company')->search([['currency_id', '=', false]]);

            if ($companiesMissingCurrency->count() > 0) {
                $companiesMissingCurrency->write(['currency_id' => $currencyId]);
            }
        } else {
            $env->model('res.company')->search()->write(['currency_id' => $currencyId]);

            $currency = $env->model('res.currency')->search([['id', '=', $currencyId]], limit: 1)->read(['name'])[0] ?? [];
            $code = strtoupper(trim((string) ($currency['name'] ?? '')));

            if ($code !== '') {
                CurrencyReferenceSeeder::activateOnly($env, $code);
            }

            CurrencyReferenceSeeder::refreshExchangeRates($env);
        }
    }

    /** @deprecated use {@see syncCompanyDefaults()} */
    public static function syncCompanyCurrency(Environment $env): void
    {
        self::syncCompanyDefaults($env);
    }
}
