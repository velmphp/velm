<?php

declare(strict_types=1);

use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\Menus;
use Velm\Views\Data\DataFileLoader;
use Velm\Views\Data\ViewsData;
use Velm\Modules\ModuleSpec;

test('views data builder produces canonical array keys', function () {
    $array = ViewsData::make()
        ->views(
            ListView::make('demo.list')->model('res.partner')->columns(['name']),
        )
        ->toArray();

    expect($array)->toHaveKeys(['VIEWS'])
        ->and($array['VIEWS'])->toHaveCount(1)
        ->and($array['VIEWS'][0]['name'])->toBe('demo.list');
});

test('data file loader accepts views data builder return value', function () {
    $path = sys_get_temp_dir().'/velm_views_data_'.uniqid();
    mkdir($path, 0777, true);
    $viewsFile = $path.DIRECTORY_SEPARATOR.'views.php';

    file_put_contents($viewsFile, <<<'PHP'
<?php
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;
return ViewsData::make()->views(
    ListView::make('x.list')->model('res.partner')->columns(['name']),
);
PHP);

    $spec = new ModuleSpec(
        name: 'fixture',
        version: [0, 1, 0],
        depends: [],
        path: $path,
        data: ['views.php'],
    );

    $loaded = (new DataFileLoader)->load($spec);

    expect($loaded['views'])->toHaveCount(1)
        ->and($loaded['views'][0]['name'])->toBe('x.list');

    unlink($viewsFile);
    rmdir($path);
});

test('views data builder supports singular and plural helpers', function (): void {
    $m = new Menus('partners');
    $list = ListView::make('partner.list')->model('res.partner')->columns(['name']);
    $inherit = InheritView::make('partner.list.ext')->extends('partners.partner.list');
    $menu = $m->item('partners', 'Partners')->view('partner.list');

    $array = ViewsData::make()
        ->view($list)
        ->views(ListView::make('partner.other')->model('res.partner')->columns(['email']))
        ->inherit($inherit)
        ->inherits(InheritView::make('partner.form.ext')->extends('partners.partner.form'))
        ->menu($menu)
        ->menus($m->group('business', 'Business'))
        ->toArray();

    expect($array)->toHaveKeys(['VIEWS', 'VIEW_INHERITS', 'MENUS'])
        ->and($array['VIEWS'])->toHaveCount(2)
        ->and($array['VIEW_INHERITS'])->toHaveCount(2)
        ->and($array['MENUS'])->not->toBeEmpty();
});

test('views data toArray omits empty sections', function (): void {
    expect(ViewsData::make()->toArray())->toBe([]);
});

test('data file loader returns empty arrays when module has no data files', function (): void {
    $spec = new ModuleSpec(
        name: 'empty',
        version: [0, 1, 0],
        depends: [],
        path: sys_get_temp_dir(),
        data: [],
    );

    expect((new DataFileLoader)->load($spec))->toBe([
        'views' => [],
        'view_inherits' => [],
        'menus' => [],
    ]);
});

test('data file loader rejects missing and invalid data files', function (): void {
    $path = sys_get_temp_dir().'/velm_data_loader_'.uniqid();
    mkdir($path, 0777, true);

    $spec = new ModuleSpec(
        name: 'fixture',
        version: [0, 1, 0],
        depends: [],
        path: $path,
        data: ['missing.php'],
    );

    expect(fn () => (new DataFileLoader)->load($spec))
        ->toThrow(RuntimeException::class, 'not found');

    file_put_contents($path.DIRECTORY_SEPARATOR.'notes.txt', 'x');
    $spec = new ModuleSpec(
        name: 'fixture',
        version: [0, 1, 0],
        depends: [],
        path: $path,
        data: ['notes.txt'],
    );

    expect(fn () => (new DataFileLoader)->load($spec))
        ->toThrow(RuntimeException::class, 'must be a .php file');

    file_put_contents($path.DIRECTORY_SEPARATOR.'bad.php', <<<'PHP'
<?php
return 123;
PHP);
    $spec = new ModuleSpec(
        name: 'fixture',
        version: [0, 1, 0],
        depends: [],
        path: $path,
        data: ['bad.php'],
    );

    expect(fn () => (new DataFileLoader)->load($spec))
        ->toThrow(RuntimeException::class, 'must return ViewsData or an array');

    unlink($path.DIRECTORY_SEPARATOR.'notes.txt');
    unlink($path.DIRECTORY_SEPARATOR.'bad.php');
    rmdir($path);
});

test('data file loader expands raw array declarations', function (): void {
    $path = sys_get_temp_dir().'/velm_data_loader_array_'.uniqid();
    mkdir($path, 0777, true);
    $dataFile = $path.DIRECTORY_SEPARATOR.'data.php';

    file_put_contents($dataFile, <<<'PHP'
<?php
return [
    'VIEWS' => [['name' => 'raw.list']],
    'VIEW_INHERITS' => [['name' => 'raw.inherit']],
    'MENUS' => [['name' => 'raw.menu']],
];
PHP);

    $loaded = (new DataFileLoader)->load(new ModuleSpec(
        name: 'fixture',
        version: [0, 1, 0],
        depends: [],
        path: $path,
        data: ['data.php'],
    ));

    expect($loaded['views'][0]['name'])->toBe('raw.list')
        ->and($loaded['view_inherits'][0]['name'])->toBe('raw.inherit')
        ->and($loaded['menus'][0]['name'])->toBe('raw.menu');

    unlink($dataFile);
    rmdir($path);
});

test('data file loader expands view declaration objects from arrays', function (): void {
    $path = sys_get_temp_dir().'/velm_data_loader_decl_'.uniqid();
    mkdir($path, 0777, true);
    $dataFile = $path.DIRECTORY_SEPARATOR.'declarations.php';

    file_put_contents($dataFile, <<<'PHP'
<?php
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('partners');

return [
    'VIEWS' => [ListView::make('decl.list')->model('res.partner')->columns(['name'])],
    'VIEW_INHERITS' => [InheritView::make('decl.ext')->extends('partners.decl.list')],
    'MENUS' => [$m->item('decl.item', 'Decl')->view('decl.list')],
];
PHP);

    $loaded = (new DataFileLoader)->load(new ModuleSpec(
        name: 'fixture',
        version: [0, 1, 0],
        depends: [],
        path: $path,
        data: ['declarations.php'],
    ));

    expect($loaded['views'][0]['name'])->toBe('decl.list')
        ->and($loaded['view_inherits'][0]['name'])->toBe('decl.ext')
        ->and($loaded['menus'][0]['name'])->toBe('decl.item');

    unlink($dataFile);
    rmdir($path);
});
