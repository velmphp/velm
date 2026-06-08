<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

/**
 * Resolves the local ISO-3166 alpha-2 country code (outbound IP geolocation).
 */
final class GeoCountryDetector
{
    private const DETECT_URL = 'https://ipapi.co/json/';

    public function __construct(
        private readonly ?GeoHttpGateway $http = null,
        private readonly ?string $overrideCode = null,
    ) {}

    public function detect(): ?string
    {
        $override = $this->overrideCode ?? self::configuredCode();

        if ($override !== null && $override !== '') {
            return strtoupper($override);
        }

        try {
            $http = $this->http ?? new StreamGeoHttpGateway(timeoutSeconds: 5);
            $payload = $http->get(self::DETECT_URL);
            $code = strtoupper((string) ($payload['country_code'] ?? ''));

            if (strlen($code) === 2) {
                return $code;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private static function configuredCode(): ?string
    {
        if (function_exists('config')) {
            $fromConfig = config('velm.geo_country');

            if (is_string($fromConfig) && $fromConfig !== '') {
                return strtoupper($fromConfig);
            }
        }

        $fromEnv = getenv('VELM_GEO_COUNTRY');

        return is_string($fromEnv) && $fromEnv !== '' ? strtoupper($fromEnv) : null;
    }
}
