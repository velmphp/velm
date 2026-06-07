<?php

declare(strict_types=1);

use Velm\Framework\Facades\Velm;
use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('velm facade resolves environment from container', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    app(\Velm\Framework\VelmManager::class)->installBootstrap();

    expect(Velm::environment())->toBeInstanceOf(\Velm\Environment::class)
        ->and(Velm::addonPaths())->not->toBeEmpty();
});
