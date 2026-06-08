<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\GeoData\StreamGeoHttpGateway;

/**
 * Imports ISO-4217 currencies from the Rest Countries API.
 */
final class CurrencyApiImporter
{
    private const REST_COUNTRIES_URL = 'https://restcountries.com/v3.1/all?fields=currencies';

    public function __construct(
        private readonly GeoHttpGateway $http = new StreamGeoHttpGateway(),
    ) {}

    /**
     * @return list<array{code: string, full_name: string, symbol: string, decimal_places: int}>
     */
    public function fetchProfiles(): array
    {
        try {
            $payload = $this->http->get(self::REST_COUNTRIES_URL);
        } catch (\Throwable) {
            return $this->fallbackProfiles();
        }

        if (! is_array($payload) || $payload === []) {
            return $this->fallbackProfiles();
        }

        /** @var array<string, array{full_name: string, symbol: string, decimal_places: int}> $profiles */
        $profiles = [];

        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $currencies = $row['currencies'] ?? null;

            if (! is_array($currencies)) {
                continue;
            }

            foreach ($currencies as $code => $info) {
                if (! is_string($code) || ! is_array($info)) {
                    continue;
                }

                $normalized = strtoupper(trim($code));

                if ($normalized === '' || isset($profiles[$normalized])) {
                    continue;
                }

                $name = trim((string) ($info['name'] ?? $normalized));
                $symbol = trim((string) ($info['symbol'] ?? $normalized));

                $profiles[$normalized] = [
                    'full_name' => $name !== '' ? $name : $normalized,
                    'symbol' => $symbol !== '' ? $symbol : $normalized,
                    'decimal_places' => CurrencyBootstrap::decimalPlacesForCode($normalized),
                ];
            }
        }

        if ($profiles === []) {
            return $this->fallbackProfiles();
        }

        ksort($profiles);

        $rows = [];

        foreach ($profiles as $code => $profile) {
            $rows[] = [
                'code' => $code,
                'full_name' => $profile['full_name'],
                'symbol' => $profile['symbol'],
                'decimal_places' => $profile['decimal_places'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{code: string, full_name: string, symbol: string, decimal_places: int}>
     */
    private function fallbackProfiles(): array
    {
        $rows = [];

        foreach (CurrencyBootstrap::PROFILES as $code => $profile) {
            $rows[] = [
                'code' => $code,
                'full_name' => $profile['full_name'],
                'symbol' => $profile['symbol'],
                'decimal_places' => $profile['decimal_places'],
            ];
        }

        return $rows;
    }
}
