<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

use Velm\Environment;

final class GeoReferenceSeedAction
{
    public function __construct(
        private readonly GeoCountryDetector $detector = new GeoCountryDetector(),
        private readonly GeoCountryProfileLoader $loader = new GeoCountryProfileLoader(),
    ) {}

    public function run(Environment $env): void
    {
        if (! $env->registry->has('res.continent') || ! $env->registry->has('res.country')) {
            return;
        }

        $code = $this->detector->detect();

        if ($code === null) {
            return;
        }

        $values = $this->loader->load($code);

        if ($values === null) {
            return;
        }

        $continentCode = $values['_continent_code'] ?? null;
        unset($values['_continent_code']);

        if (is_string($continentCode) && $continentCode !== '') {
            $values['continent_id'] = GeoContinentCatalog::ensure($env, $continentCode);
        }

        $values = GeoCurrencyLinker::apply($env, $values);

        $this->upsertCountry($env, $values);
        GeoCurrencyLinker::syncCompanyDefaults($env);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsertCountry(Environment $env, array $values): void
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
