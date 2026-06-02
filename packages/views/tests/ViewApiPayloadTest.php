<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Views\ViewNotFoundException;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('api payload returns resolved arch separate from metadata', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $payload = (new ViewRegistry)->apiPayload($env, 'partners', 'partner.list');

    expect($payload)->toHaveKeys(['id', 'module', 'name', 'model', 'view_type', 'arch'])
        ->and($payload['arch'])->toHaveKey('fields')
        ->and($payload['arch'])->not->toHaveKey('view_type');
});

test('api payload throws view not found', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    (new ViewRegistry)->apiPayload($env, 'partners', 'nope');
})->throws(ViewNotFoundException::class);
