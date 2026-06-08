<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

/**
 * Loads a single country profile from restcountries.com.
 */
final class GeoCountryProfileLoader
{
    private const FIELDS = 'cca2,cca3,name,capital,population,continents,idd,currencies,flag';

    public function __construct(
        private readonly GeoHttpGateway $http = new StreamGeoHttpGateway(),
    ) {}

    /**
     * @return array<string, mixed>|null Country values plus {@code _continent_code} for seeding.
     */
    public function load(string $code): ?array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        try {
            $url = 'https://restcountries.com/v3.1/alpha/'.rawurlencode($code).'?fields='.self::FIELDS;
            $row = $this->http->get($url);

            if (! is_array($row)) {
                return null;
            }

            return self::fromRestCountriesRow($row);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    public static function fromRestCountriesRow(array $row): ?array
    {
        $code = strtoupper((string) ($row['cca2'] ?? ''));

        if ($code === '') {
            return null;
        }

        $continentName = is_array($row['continents'] ?? null) ? ($row['continents'][0] ?? null) : null;
        $continentCode = is_string($continentName) ? GeoContinentCatalog::codeForName($continentName) : null;

        $name = is_array($row['name'] ?? null)
            ? (string) (($row['name']['common'] ?? $row['name']['official'] ?? '') ?: '')
            : '';

        if ($name === '') {
            return null;
        }

        $capital = null;

        if (is_array($row['capital'] ?? null) && ($row['capital'][0] ?? '') !== '') {
            $capital = (string) $row['capital'][0];
        }

        return [
            'name' => $name,
            'code' => $code,
            'iso3' => strtoupper((string) ($row['cca3'] ?? '')),
            'capital' => $capital,
            'population' => is_numeric($row['population'] ?? null) ? (int) $row['population'] : null,
            '_currency_code' => self::currencyCode($row),
            'phone_code' => self::phoneCode($row),
            'flag_emoji' => is_string($row['flag'] ?? null) ? $row['flag'] : null,
            '_continent_code' => $continentCode,
        ];
    }

    /**
     * @param  array<string, mixed>  $country
     */
    public static function phoneCode(array $country): ?string
    {
        $idd = $country['idd'] ?? null;

        if (! is_array($idd)) {
            return null;
        }

        $root = (string) ($idd['root'] ?? '');
        $suffixes = $idd['suffixes'] ?? [];

        if ($root === '') {
            return null;
        }

        $suffix = is_array($suffixes) && isset($suffixes[0]) ? (string) $suffixes[0] : '';

        return ltrim($root.$suffix, '+') ?: null;
    }

    /**
     * @param  array<string, mixed>  $country
     */
    public static function currencyCode(array $country): ?string
    {
        $currencies = $country['currencies'] ?? null;

        if (! is_array($currencies) || $currencies === []) {
            return null;
        }

        $code = array_key_first($currencies);

        return is_string($code) ? strtoupper($code) : null;
    }
}
