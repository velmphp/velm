<?php

declare(strict_types=1);

use Velm\Filament\Arch\ArchTableConfigurator;
use Velm\Filament\Pages\PartnerListPage;
use Velm\Filament\Support\PartnerViews;
use Velm\Filament\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('partner list page declares partner list arch', function (): void {
    $method = new ReflectionMethod(PartnerListPage::class, 'arch');
    $method->setAccessible(true);

    expect($method->invoke(null))->toBe(PartnerViews::list());
});

test('partner list arch loads rows through the same table pipeline as ArchListPage', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Velm SA', 'active' => true]);

    $records = (new ArchTableConfigurator)->fetchRecords(PartnerViews::list(), $env);

    expect($records)->toHaveCount(1)
        ->and($records->first()['name'])->toBe('Velm SA');
});
