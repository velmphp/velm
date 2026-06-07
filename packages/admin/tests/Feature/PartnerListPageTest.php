<?php

declare(strict_types=1);

use Velm\Admin\Arch\ArchTableConfigurator;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Tests\TestCase;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('partner list page resolves stored partner list arch', function (): void {
    $env = app(\Velm\Environment::class);
    $expected = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $method = new ReflectionMethod(PartnerListPage::class, 'arch');
    $method->setAccessible(true);

    expect($method->invoke(app(PartnerListPage::class)))->toBe($expected);
});

test('partner list click-to-open resolves detail record url', function (): void {
    $page = app(PartnerListPage::class);
    $open = (new ReflectionMethod(PartnerListPage::class, 'openRecordUrl'))->invoke($page, 42);

    expect($open)->toBeString()
        ->toContain('/velm/views/partners/partner.detail/42');
});

test('partner list arch loads rows through the same table pipeline as ArchListPage', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Velm SA List Test', 'active' => true]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');
    $records = (new ArchTableConfigurator)->fetchRecords($arch, $env);
    $record = $records->firstWhere('name', 'Velm SA List Test');

    expect($record)->not->toBeNull()
        ->and($record['name'])->toBe('Velm SA List Test');
});
