<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\ModuleSpec;

test('manifest builder produces canonical array keys', function (): void {
    $array = Manifest::make('demo')
        ->version(1, 2, 3)
        ->depends('base', 'web')
        ->data('data/views.php')
        ->models('App\\Demo\\Model')
        ->summary('Demo module')
        ->description('Longer text')
        ->category('Tools')
        ->author('Velm')
        ->icon('heroicon-o-cube')
        ->toArray();

    expect($array)->toBe([
        'NAME' => 'demo',
        'VERSION' => [1, 2, 3],
        'DEPENDS' => ['base', 'web'],
        'DATA' => ['data/views.php'],
        'MODELS' => ['App\\Demo\\Model'],
        'SUMMARY' => 'Demo module',
        'DESCRIPTION' => 'Longer text',
        'CATEGORY' => 'Tools',
        'AUTHOR' => 'Velm',
        'ICON' => 'heroicon-o-cube',
    ]);
});

test('manifest accepts version as array', function (): void {
    expect(Manifest::make('x')->version([2, 0])->toArray()['VERSION'])->toBe([2, 0]);
});

test('module spec reads manifest builder via toArray', function (): void {
    $spec = ModuleSpec::fromManifest(
        Manifest::make('partners')->version(0, 1, 0)->depends('base')->summary('Partners')->toArray(),
        '/tmp/partners',
    );

    expect($spec->name)->toBe('partners')
        ->and($spec->depends)->toBe(['base'])
        ->and($spec->summary)->toBe('Partners');
});

test('toArray requires version', function (): void {
    expect(fn () => Manifest::make('incomplete')->toArray())
        ->toThrow(LogicException::class);
});
