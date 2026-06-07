<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\ComputedArticle;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

function computedFieldEnvironment(): Environment
{
    return Registry::using(function (Registry $registry): Environment {
        $registry->register(ComputedArticle::class);

        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });
}

test('unstored computed field resolves on read', function (): void {
    $env = computedFieldEnvironment();
    $article = $env->model('test.article')->create([
        'title' => 'Velm',
        'subtitle' => 'RC3',
    ]);

    $row = $article->read(['headline'])[0];

    expect($row['headline'])->toBe('Velm: RC3');
});

test('stored computed field persists and recomputes on write', function (): void {
    $env = computedFieldEnvironment();
    $article = $env->model('test.article')->create(['title' => 'Hi']);

    expect($article->read(['score'])[0]['score'])->toBe(2);

    $article->write(['title' => 'Hello']);

    expect($article->read(['score'])[0]['score'])->toBe(5);
});

test('cannot write computed fields directly', function (): void {
    $env = computedFieldEnvironment();
    $article = $env->model('test.article')->create(['title' => 'Velm']);

    expect(fn () => $article->write(['headline' => 'Nope']))
        ->toThrow(InvalidArgumentException::class, 'computed field headline');
});

test('dependency write refreshes unstored computed values', function (): void {
    $env = computedFieldEnvironment();
    $article = $env->model('test.article')->create([
        'title' => 'A',
        'subtitle' => 'B',
    ]);

    expect($article->read(['headline'])[0]['headline'])->toBe('A: B');

    $article->write(['subtitle' => 'C']);

    expect($article->read(['headline'])[0]['headline'])->toBe('A: C');
});
