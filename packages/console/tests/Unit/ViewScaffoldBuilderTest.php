<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ScaffoldRegistryLoader;
use Velm\Console\Scaffold\ViewScaffoldBuilder;

test('view scaffold builder produces list columns and form sections for partner', function (): void {
    $root = dirname(__DIR__, 3).'/modules/modules';
    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $root);
    $built = (new ViewScaffoldBuilder)->build($registry, 'res.partner');

    expect($built['list'])->not->toBeEmpty()
        ->and($built['sections'])->not->toBeEmpty()
        ->and(collect($built['list'])->join(' '))->toContain('name');
});

test('view scaffold builder throws for unknown model', function (): void {
    $root = dirname(__DIR__, 3).'/modules/modules';
    $registry = (new ScaffoldRegistryLoader)->loadForModule('base', $root);

    (new ViewScaffoldBuilder)->build($registry, 'unknown.model');
})->throws(InvalidArgumentException::class);
