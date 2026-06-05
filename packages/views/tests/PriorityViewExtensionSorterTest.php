<?php

declare(strict_types=1);

use Velm\Views\Arch\PriorityViewExtensionSorter;

test('priority sorter orders by priority then id', function (): void {
    $sorted = (new PriorityViewExtensionSorter)->sort([
        ['id' => 3, 'module' => 'b', 'priority' => 20],
        ['id' => 1, 'module' => 'a', 'priority' => 10],
        ['id' => 2, 'module' => 'c', 'priority' => 20],
    ]);

    expect(array_column($sorted, 'id'))->toBe([1, 2, 3]);
});
