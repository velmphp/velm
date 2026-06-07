<?php

declare(strict_types=1);

use Velm\Admin\Arch\ListColumnHeaders;
use Velm\Admin\Tests\TestCase;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('list column headers resolve registry fields for extended models', function (): void {
    $env = app(\Velm\Environment::class);
    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $headers = (new ListColumnHeaders)->fromArch($arch, $env);

    expect($headers)->not->toBeEmpty()
        ->and(collect($headers)->pluck('name'))->toContain('name');
});

test('list column headers from model include boolean and many2one metadata', function (): void {
    $env = app(\Velm\Environment::class);
    $headers = (new ListColumnHeaders)->fromModel('res.partner', $env);

    $byName = collect($headers)->keyBy('name');

    expect($byName->has('name'))->toBeTrue()
        ->and($byName->has('active'))->toBeTrue()
        ->and($byName['active']['filter_kind'])->toBe('boolean')
        ->and($byName->has('country_id'))->toBeTrue()
        ->and($byName['country_id']['filter_kind'])->toBe('m2o')
        ->and($byName['country_id']['comodel'])->toBe('res.country');
});
