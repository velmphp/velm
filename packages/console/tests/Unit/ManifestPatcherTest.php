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

test('manifest patcher appendModel adds to multiline models block', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-models-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
use App\Models\Existing;
return Manifest::make('patch_models')
    ->version(0, 1, 0)
    ->depends('base')
    ->models(
        Existing::class,
    );
PHP);

    ManifestPatcher::appendModel($manifest, 'App\\Models\\Another', 'Another');

    $text = file_get_contents($manifest);

    expect($text)->toContain('Another::class')
        ->and($text)->toContain('use App\\Models\\Another;');

    @unlink($manifest);
    @rmdir($dir);
});

test('manifest patcher appendModel appends inline models call', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-inline-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
return Manifest::make('patch_inline')
    ->version(0, 1, 0)
    ->depends('base');
PHP);

    ManifestPatcher::appendModel($manifest, 'App\\Models\\Inline', 'Inline');

    expect(file_get_contents($manifest))->toContain('->models(Inline::class)');

    @unlink($manifest);
    @rmdir($dir);
});

test('manifest patcher appendData skips duplicate entries', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-dup-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
return Manifest::make('patch_dup')
    ->version(0, 1, 0)
    ->depends('base')
    ->data('views/extra.php');
PHP);

    ManifestPatcher::appendData($manifest, 'views/extra.php');

    expect(substr_count(file_get_contents($manifest), 'views/extra.php'))->toBe(1);

    @unlink($manifest);
    @rmdir($dir);
});

test('manifest patcher read throws when manifest is missing', function (): void {
    ManifestPatcher::appendModel('/tmp/no-manifest-'.uniqid('', true).'/__velm__.php', 'App\\Models\\X', 'X');
})->throws(RuntimeException::class, 'Manifest not found');
