<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData;

final class StreamGeoHttpGateway implements GeoHttpGateway
{
    public function __construct(
        private readonly int $timeoutSeconds = 120,
    ) {}

    public function get(string $url): array
    {
        return $this->request('GET', $url);
    }

    public function post(string $url, array $body): array
    {
        return $this->request('POST', $url, $body);
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        $headers = "Accept: application/json\r\n";

        if ($body !== null) {
            $headers .= "Content-Type: application/json\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : '',
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $raw = file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new \RuntimeException("Geo HTTP request failed for {$url}");
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException("Geo HTTP response was not JSON for {$url}");
        }

        return $decoded;
    }
}
