<?php

declare(strict_types=1);

use Velm\Domain\Domain;
use Velm\Domain\DomainCompiler;

test('normalizeDomain inserts implicit AND between adjacent leaves', function (): void {
    $normalized = DomainCompiler::normalizeDomain([
        ['active', '=', true],
        ['name', 'ilike', '%acme%'],
    ]);

    expect($normalized)->toBe([
        '&',
        ['active', '=', true],
        ['name', 'ilike', '%acme%'],
    ]);
});

test('normalizeDomain accepts explicit OR prefix groups', function (): void {
    $normalized = DomainCompiler::normalizeDomain([
        '|',
        ['name', 'ilike', '%acme%'],
        ['name', 'ilike', '%corp%'],
    ]);

    expect($normalized)->toBe([
        '|',
        ['name', 'ilike', '%acme%'],
        ['name', 'ilike', '%corp%'],
    ]);
});

test('expandOrGroups converts legacy __or__ leaves', function (): void {
    $expanded = DomainCompiler::expandOrGroups([
        ['__or__', 'ilike', [
            ['name', 'ilike', '%acme%'],
            ['name', 'ilike', '%corp%'],
        ]],
    ]);

    expect($expanded)->toBe([
        '|',
        ['name', 'ilike', '%acme%'],
        ['name', 'ilike', '%corp%'],
    ]);
});

test('compileWhere builds OR and NOT clauses', function (): void {
    $compiler = new DomainCompiler;
    $params = [];

    $sql = $compiler->compileWhere(
        [
            '&',
            ['active', '=', true],
            '|',
            ['name', 'ilike', '%acme%'],
            ['name', 'ilike', '%corp%'],
        ],
        function (Domain $leaf) use (&$params): string {
            $params[] = $leaf->field;
            $params[] = $leaf->operator;
            $params[] = $leaf->value;

            return '"'.$leaf->field.'" '.$leaf->operator.' ?';
        },
        $params,
    );

    expect($sql)->toBe('("active" = ? AND ("name" ilike ? OR "name" ilike ?))')
        ->and($params)->toBe(['active', '=', true, 'name', 'ilike', '%acme%', 'name', 'ilike', '%corp%']);
});

test('compileWhere rejects malformed domains', function (): void {
    $compiler = new DomainCompiler;
    $params = [];

    expect(fn () => $compiler->compileWhere(
        [['active', '=', true]],
        function (Domain $leaf) use (&$params): string {
            return '"active" = ?';
        },
        $params,
    ))->not->toThrow(\InvalidArgumentException::class);

    expect(fn () => $compiler->compileWhere(
        ['|', ['active', '=', true]],
        function (Domain $leaf) use (&$params): string {
            return '"active" = ?';
        },
        $params,
    ))->toThrow(\InvalidArgumentException::class);
});
