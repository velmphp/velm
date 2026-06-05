<?php

declare(strict_types=1);

use Velm\Modules\ModuleUninstallPreview;

test('blockers summarize reverse dependencies in one line', function (): void {
    $preview = new ModuleUninstallPreview(
        module: 'partners',
        canUninstall: false,
        reverseDependencies: ['partners_ext', 'partners_pro'],
    );

    expect($preview->blockers())->toBe([
        'The following modules depend on it: partners_ext, partners_pro',
    ]);
});

test('blockers omit model extensions already listed as reverse dependencies', function (): void {
    $preview = new ModuleUninstallPreview(
        module: 'partners',
        canUninstall: false,
        reverseDependencies: ['partners_ext'],
        modelExtensions: ['partners_ext', 'partners_ext_independent'],
    );

    expect($preview->blockers())->toBe([
        'The following modules depend on it: partners_ext',
        'The following modules extend its models: partners_ext_independent',
    ]);
});
