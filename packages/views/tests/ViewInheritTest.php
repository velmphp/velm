<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('view inherit extension patches stored list arch', function () {
    $roots = [
        dirname(__DIR__, 2).'/modules/modules',
        dirname(__DIR__, 2).'/modules/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    $env = $installer->environment($roots);
    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $nameField = collect($arch['fields'])->firstWhere('name', 'name');

    expect($nameField)->not->toBeNull()
        ->and($nameField['label'] ?? null)->toBe('Partner name');
});

test('resolve works when requesting an extension view by name', function () {
    $roots = [
        dirname(__DIR__, 2).'/modules/modules',
        dirname(__DIR__, 2).'/modules/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'partners', 'partners_ext']);

    $env = $installer->environment($roots);
    $arch = (new ViewRegistry)->arch($env, 'partners_ext', 'partner.list.ext');

    expect($arch['model'])->toBe('res.partner')
        ->and(collect($arch['fields'])->firstWhere('name', 'name')['label'] ?? null)->toBe('Partner name');
});
