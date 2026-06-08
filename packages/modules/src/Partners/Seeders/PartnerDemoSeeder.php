<?php

declare(strict_types=1);

namespace Velm\Modules\Partners\Seeders;

use Velm\Environment;
use Velm\Modules\Seeding\ModuleSeeder;

/**
 * Idempotent demo contacts for list, kanban, graph, and pivot views.
 */
final class PartnerDemoSeeder implements ModuleSeeder
{
    public static function run(Environment $env): void
    {
        if (! $env->registry->has('res.partner') || ! $env->registry->has('res.country')) {
            return;
        }

        $companyId = self::defaultCompanyId($env);
        $countryId = self::firstCountryId($env);

        self::upsertPartner($env, [
            'name' => 'Velm SA',
            'active' => true,
            'is_company' => true,
            'country_id' => $countryId,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Brussels Consulting BV',
            'active' => true,
            'is_company' => true,
            'country_id' => $countryId,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Jan de Vries',
            'active' => true,
            'is_company' => false,
            'country_id' => $countryId,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Lyon Industries',
            'active' => false,
            'is_company' => true,
            'country_id' => $countryId,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Legacy Partner GmbH',
            'active' => false,
            'is_company' => true,
            'country_id' => $countryId,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Anonymous Contact',
            'active' => true,
            'is_company' => false,
            'country_id' => false,
            'company_id' => $companyId,
        ]);
    }

    private static function defaultCompanyId(Environment $env): int|false|null
    {
        if (! $env->registry->has('res.company')) {
            return false;
        }

        $existing = $env->model('res.company')->search([], limit: 1);

        if ($existing->count() === 0) {
            return false;
        }

        return $existing->ids()[0];
    }

    private static function firstCountryId(Environment $env): int|false
    {
        $existing = $env->model('res.country')->search([], limit: 1);

        if ($existing->count() === 0) {
            return false;
        }

        return $existing->ids()[0];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function upsertPartner(Environment $env, array $values): void
    {
        $name = (string) ($values['name'] ?? '');

        if ($name === '') {
            return;
        }

        $existing = $env->model('res.partner')->search([['name', '=', $name]], limit: 1);

        if ($existing->count() > 0) {
            $existing->write($values);

            return;
        }

        $env->model('res.partner')->create($values);
    }
}
