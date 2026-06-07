<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData\Seeders;

use Velm\Environment;
use Velm\Modules\Seeding\ModuleSeeder;

/**
 * Seeds continents and a starter set of European countries for demos.
 */
final class GeoReferenceSeeder implements ModuleSeeder
{
    public static function run(Environment $env): void
    {
        if (! $env->registry->has('res.continent') || ! $env->registry->has('res.country')) {
            return;
        }

        $europe = self::continentId($env, 'EU', 'Europe');
        self::continentId($env, 'AS', 'Asia');
        self::continentId($env, 'AF', 'Africa');
        self::continentId($env, 'NA', 'North America');
        self::continentId($env, 'SA', 'South America');
        self::continentId($env, 'OC', 'Oceania');
        self::continentId($env, 'AN', 'Antarctica');

        self::upsertCountry($env, [
            'name' => 'Belgium',
            'code' => 'BE',
            'iso3' => 'BEL',
            'continent_id' => $europe,
            'capital' => 'Brussels',
            'currency_code' => 'EUR',
            'phone_code' => '32',
            'flag_emoji' => '🇧🇪',
        ]);
        self::upsertCountry($env, [
            'name' => 'Netherlands',
            'code' => 'NL',
            'iso3' => 'NLD',
            'continent_id' => $europe,
            'capital' => 'Amsterdam',
            'currency_code' => 'EUR',
            'phone_code' => '31',
            'flag_emoji' => '🇳🇱',
        ]);
        self::upsertCountry($env, [
            'name' => 'France',
            'code' => 'FR',
            'iso3' => 'FRA',
            'continent_id' => $europe,
            'capital' => 'Paris',
            'currency_code' => 'EUR',
            'phone_code' => '33',
            'flag_emoji' => '🇫🇷',
        ]);
        self::upsertCountry($env, [
            'name' => 'Germany',
            'code' => 'DE',
            'iso3' => 'DEU',
            'continent_id' => $europe,
            'capital' => 'Berlin',
            'currency_code' => 'EUR',
            'phone_code' => '49',
            'flag_emoji' => '🇩🇪',
        ]);

        // Keep the other continents discoverable even before a full geography import.
    }

    private static function continentId(Environment $env, string $code, string $name): int
    {
        $existing = $env->model('res.continent')->search([['code', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            return $existing->ids()[0];
        }

        return $env->model('res.continent')->create([
            'code' => $code,
            'name' => $name,
        ])->ids()[0];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function upsertCountry(Environment $env, array $values): void
    {
        $code = (string) ($values['code'] ?? '');

        if ($code === '') {
            return;
        }

        $existing = $env->model('res.country')->search([['code', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            $existing->write($values);

            return;
        }

        $env->model('res.country')->create($values);
    }
}
