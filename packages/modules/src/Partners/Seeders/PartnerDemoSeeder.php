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
        $be = self::countryId($env, 'BE', 'Belgium');
        $nl = self::countryId($env, 'NL', 'Netherlands');
        $fr = self::countryId($env, 'FR', 'France');
        $de = self::countryId($env, 'DE', 'Germany');

        self::upsertPartner($env, [
            'name' => 'Velm SA',
            'active' => true,
            'is_company' => true,
            'country_id' => $be,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Brussels Consulting BV',
            'active' => true,
            'is_company' => true,
            'country_id' => $be,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Jan de Vries',
            'active' => true,
            'is_company' => false,
            'country_id' => $nl,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Lyon Industries',
            'active' => false,
            'is_company' => true,
            'country_id' => $fr,
            'company_id' => $companyId,
        ]);
        self::upsertPartner($env, [
            'name' => 'Legacy Partner GmbH',
            'active' => false,
            'is_company' => true,
            'country_id' => $de,
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

    private static function countryId(Environment $env, string $code, string $name): int
    {
        $existing = $env->model('res.country')->search([['code', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            return $existing->ids()[0];
        }

        return $env->model('res.country')->create([
            'name' => $name,
            'code' => $code,
        ])->ids()[0];
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
