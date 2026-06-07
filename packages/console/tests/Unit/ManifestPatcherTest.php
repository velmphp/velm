<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ManifestPatcher;

test('manifest patcher appends model import and registration', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
return Manifest::make('patch_demo')
    ->version(0, 1, 0)
    ->depends('base');
PHP);

    ManifestPatcher::appendModel($manifest, 'App\\Models\\DemoItem', 'DemoItem');

    $text = file_get_contents($manifest);

    expect($text)->toContain('use App\\Models\\DemoItem;')
        ->and($text)->toContain('DemoItem::class');

    @unlink($manifest);
    @rmdir($dir);
});

test('manifest patcher appends data file entry', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-data-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
return Manifest::make('patch_data')
    ->version(0, 1, 0)
    ->depends('base');
PHP);

    ManifestPatcher::appendData($manifest, 'views/extra.php');

    expect(file_get_contents($manifest))->toContain('views/extra.php');

    @unlink($manifest);
    @rmdir($dir);
});
