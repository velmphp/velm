<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

use Velm\Environment;

/**
 * Canonical continent codes used by geo_data (PyVelm-style).
 */
final class GeoContinentCatalog
{
    /** @var array<string, string> */
    public const CONTINENTS = [
        'EU' => 'Europe',
        'AS' => 'Asia',
        'AF' => 'Africa',
        'NA' => 'North America',
        'SA' => 'South America',
        'OC' => 'Oceania',
        'AN' => 'Antarctica',
    ];

    public static function ensure(Environment $env, string $code): int
    {
        $name = self::CONTINENTS[$code] ?? null;

        if ($name === null) {
            throw new \InvalidArgumentException("Unknown continent code {$code}.");
        }

        return self::continentId($env, $code, $name);
    }

    /**
     * @return array<string, int> continent code => record id
     */
    public static function ensureAll(Environment $env): array
    {
        $ids = [];

        foreach (self::CONTINENTS as $code => $name) {
            $ids[$code] = self::continentId($env, $code, $name);
        }

        return $ids;
    }

    public static function codeForName(string $name): ?string
    {
        $normalized = strtolower(trim($name));

        foreach (self::CONTINENTS as $code => $label) {
            if (strtolower($label) === $normalized) {
                return $code;
            }
        }

        return null;
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
}
