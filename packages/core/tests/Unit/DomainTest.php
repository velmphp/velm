<?php

declare(strict_types=1);

use Velm\Domain\Domain;

test('domain fromArray builds a three element leaf', function (): void {
    $domain = Domain::fromArray(['name', 'ilike', '%acme%']);

    expect($domain->field)->toBe('name')
        ->and($domain->operator)->toBe('ilike')
        ->and($domain->value)->toBe('%acme%');
});

test('domain fromArray rejects malformed lists', function (): void {
    Domain::fromArray(['only', 'two']);
})->throws(InvalidArgumentException::class, 'three-element');

test('domain parseList returns empty for empty input', function (): void {
    expect(Domain::parseList([]))->toBe([]);
});

test('domain parseList wraps a single leaf', function (): void {
    $domains = Domain::parseList(['active', '=', true]);

    expect($domains)->toHaveCount(1)
        ->and($domains[0])->toBeInstanceOf(Domain::class)
        ->and($domains[0]->field)->toBe('active');
});

test('domain parseList maps multiple leaves', function (): void {
    $domains = Domain::parseList([
        ['active', '=', true],
        ['name', 'ilike', '%x%'],
    ]);

    expect($domains)->toHaveCount(2)
        ->and($domains[1]->field)->toBe('name');
});
