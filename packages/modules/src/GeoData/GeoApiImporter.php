<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

use Velm\Environment;

/**
 * Imports countries, states and cities from public HTTP APIs (on demand).
 */
final class GeoApiImporter
{
    private const REST_COUNTRIES_URL = 'https://restcountries.com/v3.1/all?fields=cca2,cca3,name,capital,population,continents,idd,currencies,flag';

    private const STATES_URL = 'https://countriesnow.space/api/v0.1/countries/states';

    private const CITIES_URL = 'https://countriesnow.space/api/v0.1/countries/state/cities';

    public function __construct(
        private readonly GeoHttpGateway $http = new StreamGeoHttpGateway(),
    ) {}

    /**
     * @return array{countries: int, states: int, cities: int}
     */
    public function import(Environment $env): array
    {
        if (! $env->registry->has('res.continent')
            || ! $env->registry->has('res.country')
            || ! $env->registry->has('res.country.state')
            || ! $env->registry->has('res.city')) {
            throw new \RuntimeException('Geo data models are not installed.');
        }

        $continentIds = GeoContinentCatalog::ensureAll($env);

        $countriesPayload = $this->http->get(self::REST_COUNTRIES_URL);
        $countryCount = 0;
        $countryIdsByCode = [];
        $countryNamesByCode = [];

        foreach ($countriesPayload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper((string) ($row['cca2'] ?? ''));

            if ($code === '') {
                continue;
            }

            $values = GeoCountryProfileLoader::fromRestCountriesRow($row);

            if ($values === null) {
                continue;
            }

            $continentCode = $values['_continent_code'] ?? null;
            unset($values['_continent_code']);
            $values['continent_id'] = is_string($continentCode) && $continentCode !== ''
                ? ($continentIds[$continentCode] ?? null)
                : null;
            $values = GeoCurrencyLinker::apply($env, $values);

            $name = (string) ($values['name'] ?? '');

            $countryIdsByCode[$code] = $this->upsertCountry($env, $values);
            $countryNamesByCode[$code] = $name;
            $countryCount++;
        }

        $statesPayload = $this->http->get(self::STATES_URL);
        $stateCount = 0;
        $stateIdsByCountryAndName = [];

        foreach (($statesPayload['data'] ?? []) as $countryRow) {
            if (! is_array($countryRow)) {
                continue;
            }

            $iso2 = strtoupper((string) ($countryRow['iso2'] ?? ''));
            $countryId = $countryIdsByCode[$iso2] ?? null;

            if ($countryId === null) {
                continue;
            }

            foreach (($countryRow['states'] ?? []) as $stateRow) {
                if (! is_array($stateRow)) {
                    continue;
                }

                $stateName = trim((string) ($stateRow['name'] ?? ''));

                if ($stateName === '') {
                    continue;
                }

                $shortCode = trim((string) ($stateRow['state_code'] ?? ''));
                $code = self::stateCode($iso2, $stateName, $shortCode);

                $stateId = $this->upsertState($env, [
                    'name' => $stateName,
                    'short_code' => $shortCode !== '' ? $shortCode : null,
                    'code' => $code,
                    'type' => null,
                    'country_id' => $countryId,
                ]);

                $stateIdsByCountryAndName[$iso2][$stateName] = $stateId;
                $stateCount++;
            }
        }

        $cityCount = 0;

        foreach ($stateIdsByCountryAndName as $iso2 => $statesByName) {
            $countryName = $countryNamesByCode[$iso2] ?? null;
            $countryId = $countryIdsByCode[$iso2] ?? null;

            if ($countryName === null || $countryId === null) {
                continue;
            }

            foreach ($statesByName as $stateName => $stateId) {
                $citiesPayload = $this->http->post(self::CITIES_URL, [
                    'country' => $countryName,
                    'state' => $stateName,
                ]);

                if (($citiesPayload['error'] ?? true) !== false) {
                    continue;
                }

                $capitalName = null;
                $countryRecord = $env->model('res.country')->search([['id', '=', $countryId]], limit: 1)->read(['capital'])[0] ?? [];
                $capitalName = is_string($countryRecord['capital'] ?? null) ? $countryRecord['capital'] : null;

                foreach (($citiesPayload['data'] ?? []) as $cityName) {
                    if (! is_string($cityName) || trim($cityName) === '') {
                        continue;
                    }

                    $cityName = trim($cityName);
                    $isCapital = $capitalName !== null && strcasecmp($cityName, $capitalName) === 0;

                    $this->upsertCity($env, [
                        'name' => $cityName,
                        'country_id' => $countryId,
                        'state_id' => $stateId,
                        'is_capital' => $isCapital,
                    ]);

                    $cityCount++;
                }
            }
        }

        return [
            'countries' => $countryCount,
            'states' => $stateCount,
            'cities' => $cityCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsertCountry(Environment $env, array $values): int
    {
        $code = (string) ($values['code'] ?? '');

        $existing = $env->model('res.country')->search([['code', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            $existing->write($values);

            return $existing->ids()[0];
        }

        return $env->model('res.country')->create($values)->ids()[0];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsertState(Environment $env, array $values): int
    {
        $code = (string) ($values['code'] ?? '');

        $existing = $env->model('res.country.state')->search([['code', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            $existing->write($values);

            return $existing->ids()[0];
        }

        return $env->model('res.country.state')->create($values)->ids()[0];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsertCity(Environment $env, array $values): void
    {
        $name = (string) ($values['name'] ?? '');
        $countryId = (int) ($values['country_id'] ?? 0);
        $stateId = (int) ($values['state_id'] ?? 0);

        $existing = $env->model('res.city')->search([
            ['name', '=', $name],
            ['country_id', '=', $countryId],
            ['state_id', '=', $stateId],
        ], limit: 1);

        if ($existing->count() > 0) {
            $existing->write($values);

            return;
        }

        $env->model('res.city')->create($values);
    }

    private static function stateCode(string $countryCode, string $stateName, string $shortCode): string
    {
        if ($shortCode !== '') {
            return strtoupper($countryCode.'-'.$shortCode);
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '-', $stateName) ?? $stateName;
        $slug = trim(strtoupper($slug), '-');

        return strtoupper($countryCode.'-'.($slug !== '' ? $slug : 'STATE'));
    }

    /**
     * @param  array<string, mixed>  $country
     */
}
