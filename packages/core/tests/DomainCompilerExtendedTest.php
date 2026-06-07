<?php

declare(strict_types=1);

use Velm\Domain\Domain;
use Velm\Domain\DomainCompiler;

test('domain compiler expandOrGroups skips empty subs and single sub', function (): void {
    expect(DomainCompiler::expandOrGroups([
        ['__or__', 'ilike', []],
        ['__or__', 'ilike', [['name', '=', 'solo']]],
        ['__or__', 'ilike', [
            ['name', 'ilike', '%a%'],
            ['name', 'ilike', '%b%'],
        ]],
    ]))->toBe([
        ['name', '=', 'solo'],
        '|',
        ['name', 'ilike', '%a%'],
        ['name', 'ilike', '%b%'],
    ]);
});

test('domain compiler normalizeDomain accepts empty domain', function (): void {
    expect(DomainCompiler::normalizeDomain([]))->toBe([]);
});

test('domain compiler compileWhere handles empty domain and NOT prefix', function (): void {
    $compiler = new DomainCompiler;
    $params = [];

    expect($compiler->compileWhere([], fn (Domain $leaf): string => '?', $params))->toBe('');

    $sql = $compiler->compileWhere(
        ['!', ['active', '=', false]],
        fn (Domain $leaf): string => '"'.$leaf->field.'" '.$leaf->operator.' ?',
        $params,
    );

    expect($sql)->toBe('NOT ("active" = ?)');
});

test('domain compiler rejects invalid polish tokens', function (): void {
    $compiler = new DomainCompiler;
    $params = [];

    expect(fn () => $compiler->compileWhere(
        ['&'],
        fn (Domain $leaf): string => '?',
        $params,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => $compiler->compileWhere(
        [123],
        fn (Domain $leaf): string => '?',
        $params,
    ))->toThrow(InvalidArgumentException::class);
});

test('domain compiler isLeaf rejects operator tokens', function (): void {
    expect(DomainCompiler::isLeaf(['&', '=', true]))->toBeFalse()
        ->and(DomainCompiler::isLeaf(['name', '=', 'x']))->toBeTrue();
});
