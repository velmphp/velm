<?php

declare(strict_types=1);

use Velm\Views\Authoring\ListView;
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
