<?php

declare(strict_types=1);

use Velm\Modules\GeoData\StreamGeoHttpGateway;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('stream geo http gateway throws when response is not json and posts json bodies', function (): void {
    $gateway = new StreamGeoHttpGateway(timeoutSeconds: 1);

    expect(fn () => $gateway->get('data://text/plain,not-json'))
        ->toThrow(RuntimeException::class, 'Geo HTTP response was not JSON');

    expect($gateway->post('data://application/json,{"ok":true}', ['country' => 'Belgium']))
        ->toBe(['ok' => true]);
});
