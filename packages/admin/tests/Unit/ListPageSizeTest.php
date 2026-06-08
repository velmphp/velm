<?php

declare(strict_types=1);

use Velm\Admin\Support\ListPageSize;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('list page size resolves configured sizes and all option', function (): void {
    config([
        'velm.list_page_size' => 25,
        'velm.list_page_sizes' => [10, 25, 50],
    ]);

    expect(ListPageSize::default())->toBe(25)
        ->and(ListPageSize::values())->toBe([10, 25, 50, ListPageSize::ALL])
        ->and(collect(ListPageSize::options())->pluck('label')->all())->toContain('All', '25');
});

test('list page size normalizes unknown values to default', function (): void {
    config(['velm.list_page_size' => 10]);

    expect(ListPageSize::normalize(99))->toBe(10)
        ->and(ListPageSize::normalize(ListPageSize::ALL))->toBe(ListPageSize::ALL);
});

test('list page size effective per page returns total for all', function (): void {
    expect(ListPageSize::effectivePerPage(ListPageSize::ALL, 37))->toBe(37)
        ->and(ListPageSize::effectivePerPage(10, 37))->toBe(10)
        ->and(ListPageSize::effectivePerPage(10, 0))->toBe(10);
});
