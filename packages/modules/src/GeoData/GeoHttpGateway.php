<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

interface GeoHttpGateway
{
    /**
     * @return array<string, mixed>
     */
    public function get(string $url): array;

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $url, array $body): array;
}
