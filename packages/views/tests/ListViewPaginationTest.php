<?php

declare(strict_types=1);

use Velm\Views\Authoring\ListView;
use Velm\Views\Tests\TestCase;

uses(TestCase::class);

test('list view stores pagination arch key', function (): void {
    $list = ListView::make('partner.list')
        ->model('res.partner')
        ->columns(['name'])
        ->fullPagination()
        ->toArray();

    expect($list['arch']['pagination'] ?? null)->toBe('full');
});

test('list view simple pagination helper stores arch key', function (): void {
    $list = ListView::make('partner.list')
        ->model('res.partner')
        ->columns(['name'])
        ->simplePagination()
        ->toArray();

    expect($list['arch']['pagination'] ?? null)->toBe('simple');
});

test('list view rejects unknown pagination styles', function (): void {
    ListView::make('partner.list')
        ->model('res.partner')
        ->columns(['name'])
        ->pagination('infinite');
})->throws(InvalidArgumentException::class);
