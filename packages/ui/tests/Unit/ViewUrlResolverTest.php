<?php

declare(strict_types=1);

use Velm\Ui\Support\ViewUrlResolver;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

test('record and edit hrefs follow stored view slug shape', function (): void {
    $base = '/velm/views/demo_relations/tag.form';

    expect(ViewUrlResolver::recordHref($base, 3))
        ->toBe('/velm/views/demo_relations/tag.form/3')
        ->and(ViewUrlResolver::recordEditHref($base, 3))
        ->toBe('/velm/views/demo_relations/tag.form/3/edit')
        ->and(ViewUrlResolver::createHref($base))
        ->toBe('/velm/views/demo_relations/tag.form/create');
});

test('view url resolver builds href from module and view name', function (): void {
    expect(ViewUrlResolver::viewHref('partners', 'partner.form'))
        ->toBe('/velm/views/partners/partner.form');
});

test('view url resolver resolves stored views for partner model', function (): void {
    $env = app(\Velm\Environment::class);

    expect(ViewUrlResolver::formUrlForModel($env, 'res.partner'))
        ->toContain('/velm/views/partners/')
        ->and(ViewUrlResolver::listUrlForModel($env, 'res.partner'))
        ->toContain('/velm/views/partners/')
        ->and(ViewUrlResolver::recordViewUrlForModel($env, 'res.partner', \Velm\Ui\Forms\FormMode::Edit))
        ->toContain('/velm/views/partners/');
});

test('view url resolver returns null for unknown models', function (): void {
    $env = app(\Velm\Environment::class);

    expect(ViewUrlResolver::formUrlForModel($env, 'no.such.model'))->toBeNull()
        ->and(ViewUrlResolver::listUrlForModel($env, 'no.such.model'))->toBeNull();
});

test('view url resolver recordViewUrl prefers detail view in display mode', function (): void {
    $env = app(\Velm\Environment::class);

    $url = ViewUrlResolver::recordViewUrlForModel($env, 'res.partner', \Velm\Ui\Forms\FormMode::Display);

    expect($url)->toContain('/velm/views/partners/');
});
