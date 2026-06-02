<?php

declare(strict_types=1);

use Velm\Filament\Support\CompanyViews;
use Velm\Filament\Support\PartnerViews;
use Velm\Filament\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('partner form arch fields persist on create and write', function (): void {
    $env = $this->app->make(\Velm\Environment::class);

    $company = $env->model('res.company')->create(['name' => 'Velm SA']);
    $partner = $env->model('res.partner')->create([
        'name' => 'Jane Doe',
        'is_company' => false,
        'company_id' => $company->ids()[0],
        'active' => true,
    ]);

    expect($partner->read()[0]['company_id'])->toBe($company->ids()[0])
        ->and(PartnerViews::form()['model'])->toBe('res.partner');

    $partner->write(['name' => 'Jane Smith']);

    expect($partner->read()[0]['name'])->toBe('Jane Smith');
});

test('company list arch matches res.company model', function (): void {
    $env = $this->app->make(\Velm\Environment::class);

    $before = $env->model('res.company')->search()->count();
    $env->model('res.company')->create(['name' => 'Subsidiary']);

    expect(CompanyViews::list()['model'])->toBe('res.company')
        ->and($env->model('res.company')->search()->count())->toBe($before + 1);
});
