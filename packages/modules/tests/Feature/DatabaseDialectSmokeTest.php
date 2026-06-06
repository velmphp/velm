<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Tests\DialectSmokeTestCase;

uses(DialectSmokeTestCase::class);

test('install bootstrap and partners on CI database driver', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    expect(Schema::hasTable(ModuleRepository::TABLE))->toBeTrue()
        ->and(Schema::hasTable('res_partner'))->toBeTrue()
        ->and($installer->environment($roots)->model('res.partner')->search()->count())->toBeGreaterThanOrEqual(0);
})->skip(
    fn (): bool => ! in_array(getenv('DB_CONNECTION') ?: 'sqlite', ['mysql', 'pgsql'], true),
    'Dialect smoke runs only on mysql/pgsql CI matrix.',
);
