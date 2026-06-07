<?php

declare(strict_types=1);

use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
    ]);
});

test('artisan velm make module model and view scaffolders exit successfully in temp dir', function (): void {
    $tmp = sys_get_temp_dir().'/velm-make-'.uniqid('', true);
    mkdir($tmp, 0777, true);

    $this->artisan('velm:make:module', [
        'name' => 'test_addon',
        '--path' => $tmp,
    ])->assertSuccessful();

    $this->artisan('velm:make:model', [
        'model' => 'test.model',
        '--module' => 'test_addon',
        '--path' => $tmp,
    ])->assertSuccessful();

    $this->artisan('velm:make:view', [
        'model' => 'test.model',
        '--module' => 'test_addon',
        '--path' => $tmp,
        '--minimal' => true,
    ])->assertSuccessful();

    $this->artisan('velm:make:menu', [
        '--view' => 'test.list',
        '--module' => 'test_addon',
        '--path' => $tmp,
    ])->assertSuccessful();
});
