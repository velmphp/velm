<?php

declare(strict_types=1);

use Velm\Admin\Support\ListPagination;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('list pagination resolves configured default style', function (): void {
    config(['velm.list_pagination' => 'full']);

    expect(ListPagination::resolveStyle(null))->toBe('full')
        ->and(ListPagination::viewForStyle('full'))->toBe('velm-ui::pagination.full');
});

test('list pagination arch style overrides configured default', function (): void {
    config(['velm.list_pagination' => 'full']);

    expect(ListPagination::resolveStyle('simple'))->toBe('simple')
        ->and(ListPagination::viewForStyle('simple'))->toBe('velm-ui::pagination.simple');
});

test('list pagination falls back to simple for unknown styles', function (): void {
    config(['velm.list_pagination' => 'invalid']);

    expect(ListPagination::resolveStyle('nope'))->toBe('simple');
});
