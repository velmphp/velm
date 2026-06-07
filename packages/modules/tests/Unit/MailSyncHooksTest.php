<?php

declare(strict_types=1);

use Velm\Modules\Mail\MailSyncHooks;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('mail sync hooks run without error', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('mail', $roots);

    MailSyncHooks::sync($installer->environment($roots));

    expect(true)->toBeTrue();
});
