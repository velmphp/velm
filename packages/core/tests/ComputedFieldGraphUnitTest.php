<?php

declare(strict_types=1);

use Velm\Computed\ComputedFieldGraph;
use Velm\Core\Tests\Support\ComputedArticle;
use Velm\Core\Tests\Support\CyclicComputedArticle;
use Velm\Core\Tests\Support\DottedDepComputedArticle;
use Velm\Core\Tests\Support\MissingMethodComputedArticle;
use Velm\Core\Tests\Support\UnknownDepComputedArticle;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

test('computed field graph empty returns no dependents', function (): void {
    $graph = ComputedFieldGraph::empty();

    expect($graph->storedOrder('test.article'))->toBe([])
        ->and($graph->dependents('test.article', 'title'))->toBe([])
        ->and($graph->affectedComputedFields('test.article', ['title']))->toBe([]);
});

test('computed field graph tracks dependents and stored order', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(ComputedArticle::class);

        return $registry;
    });

    $graph = $registry->computedGraph();

    expect($graph->storedOrder('test.article'))->toBe(['score'])
        ->and($graph->dependents('test.article', 'title'))->toContain('headline', 'score')
        ->and($graph->affectedComputedFields('test.article', ['title']))
        ->toBe(['score', 'headline']);
});

test('computed field graph rejects missing compute method', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): Environment {
        $registry->register(MissingMethodComputedArticle::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    }))->toThrow(InvalidArgumentException::class, 'missing method missingMethod');
});

test('computed field graph rejects unknown dependency', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(UnknownDepComputedArticle::class);
    }))->toThrow(InvalidArgumentException::class, 'depends on unknown field');
});

test('computed field graph rejects stored field cycles', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(CyclicComputedArticle::class);
    }))->toThrow(InvalidArgumentException::class, 'Computed-field cycle');
});

test('computed field graph rejects dotted dependency with unknown root', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(DottedDepComputedArticle::class);
    }))->toThrow(InvalidArgumentException::class, 'depends on unknown field');
});
