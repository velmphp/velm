<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Seeders;

use Velm\Environment;
use Velm\Modules\Base\CompanyBootstrap;
use Velm\Modules\Base\CurrencyBootstrap;
use Velm\Modules\Seeding\ModuleSeeder;

/**
 * Seeds the default company currency on install; full ISO-4217 import is on demand.
 */
final class CurrencyReferenceSeeder implements ModuleSeeder
{
    public static function run(Environment $env): void
    {
        if (! $env->registry->has('res.currency')) {
            return;
        }

        $code = CompanyBootstrap::configuredDefaultCurrencyCode() ?? 'EUR';
        CurrencyBootstrap::ensureCode($env, $code);

        self::activateDefaultCurrency($env);

        if (! $env->registry->has('res.company')) {
            return;
        }

        $defaultCurrencyId = CompanyBootstrap::resolveDefaultCurrencyId($env);

        if ($defaultCurrencyId !== null) {
            $companies = $env->model('res.company')->search([['currency_id', '=', false]]);

            if ($companies->count() > 0) {
                $companies->write(['currency_id' => $defaultCurrencyId]);
            }
        }

        self::refreshExchangeRates($env);
    }

    /**
     * @param  list<array{code: string, full_name: string, symbol: string, decimal_places: int}>  $profiles
     */
    public static function importProfiles(Environment $env, array $profiles): int
    {
        $imported = 0;

        foreach ($profiles as $profile) {
            self::upsertCurrency($env, [
                'name' => $profile['code'],
                'full_name' => $profile['full_name'],
                'symbol' => $profile['symbol'],
                'decimal_places' => $profile['decimal_places'],
                'active' => false,
            ]);
            $imported++;
        }

        return $imported;
    }

    public static function activateDefaultCurrency(Environment $env): void
    {
        if (! $env->registry->has('res.currency')) {
            return;
        }

        $defaultId = CompanyBootstrap::resolveDefaultCurrencyId($env);

        if ($defaultId === null) {
            return;
        }

        $default = $env->model('res.currency')->search([['id', '=', $defaultId]], limit: 1)->read(['name'])[0] ?? [];
        $code = strtoupper(trim((string) ($default['name'] ?? '')));

        if ($code === '') {
            return;
        }

        self::activateOnly($env, $code);
    }

    public static function activateOnly(Environment $env, string $code): void
    {
        if (! $env->registry->has('res.currency')) {
            return;
        }

        $code = strtoupper(trim($code));

        if ($code === '') {
            return;
        }

        $env->model('res.currency')->search([['active', '=', true]])->write(['active' => false]);

        $match = $env->model('res.currency')->search([['name', '=', $code]], limit: 1);

        if ($match->count() > 0) {
            $match->write(['active' => true]);
        }
    }

    public static function refreshExchangeRates(Environment $env): void
    {
        self::seedExchangeRates($env);
    }

    private static function seedExchangeRates(Environment $env): void
    {
        if (! $env->registry->has('res.currency.rate') || ! $env->registry->has('res.company')) {
            return;
        }

        $today = gmdate('Y-m-d');

        foreach ($env->model('res.company')->search()->read(['id', 'currency_id']) as $company) {
            $companyId = (int) ($company['id'] ?? 0);
            $companyCurrencyId = $company['currency_id'] ?? null;

            if ($companyId === 0 || $companyCurrencyId === null || $companyCurrencyId === false) {
                continue;
            }

            $companyCurrency = $env->model('res.currency')->search([['id', '=', $companyCurrencyId]], limit: 1)->read(['name'])[0] ?? [];
            $companyCode = strtoupper((string) ($companyCurrency['name'] ?? ''));
            $companyPerEur = CurrencyBootstrap::demoRatePerEur($companyCode);

            if ($companyPerEur === null || $companyPerEur <= 0.0) {
                continue;
            }

            foreach ($env->model('res.currency')->search([['active', '=', true]])->read(['id', 'name']) as $currency) {
                $foreignCode = strtoupper((string) ($currency['name'] ?? ''));
                $foreignPerEur = CurrencyBootstrap::demoRatePerEur($foreignCode);

                if ($foreignPerEur === null || $foreignPerEur <= 0.0) {
                    continue;
                }

                self::upsertRate($env, [
                    'name' => $today,
                    'rate' => round($companyPerEur / $foreignPerEur, 6),
                    'currency_id' => (int) $currency['id'],
                    'company_id' => $companyId,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function upsertCurrency(Environment $env, array $values): void
    {
        $code = (string) ($values['name'] ?? '');

        if ($code === '') {
            return;
        }

        $existing = $env->model('res.currency')->search([['name', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            unset($values['active']);
            $existing->write($values);

            return;
        }

        $values['active'] = $values['active'] ?? false;
        $env->model('res.currency')->create($values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function upsertRate(Environment $env, array $values): void
    {
        $date = (string) ($values['name'] ?? '');
        $currencyId = (int) ($values['currency_id'] ?? 0);
        $companyId = (int) ($values['company_id'] ?? 0);

        if ($date === '' || $currencyId === 0 || $companyId === 0) {
            return;
        }

        $existing = $env->model('res.currency.rate')->search([
            ['name', '=', $date],
            ['currency_id', '=', $currencyId],
            ['company_id', '=', $companyId],
        ], limit: 1);

        if ($existing->count() > 0) {
            $existing->write($values);

            return;
        }

        $env->model('res.currency.rate')->create($values);
    }
}
