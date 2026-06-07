<?php

declare(strict_types=1);

use Velm\Admin\Arch\AnalyticsMeasures;

test('analytics measures aggregate fields filters count pseudo field', function (): void {
    $helper = new AnalyticsMeasures;

    expect($helper->aggregateFields(['__count', 'amount:sum']))->toBe(['amount:sum']);
});

test('analytics measures labels and values resolve count and aggregates', function (): void {
    $helper = new AnalyticsMeasures;

    expect($helper->label('__count'))->toBe('Count')
        ->and($helper->label('amount:sum'))->toBe('Amount Sum')
        ->and($helper->labels(['__count', 'amount:avg']))->toBe([
            '__count' => 'Count',
            'amount:avg' => 'Amount Avg',
        ]);

    $group = ['__count' => 5, 'amount_sum' => 100, 'amount_avg' => 20.5];

    expect($helper->value('__count', $group))->toBe(5)
        ->and($helper->value('amount:sum', $group))->toBe(100)
        ->and($helper->value('amount:avg', $group))->toBe(20.5)
        ->and($helper->value('missing:sum', $group))->toBeNull()
        ->and($helper->primaryValue(['amount:sum', '__count'], $group))->toBe(100);
});
