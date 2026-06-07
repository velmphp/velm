<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\ComputedArticle;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

test('computed field graph detects dependency order', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(ComputedArticle::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $article = $env->model('test.article')->create([
        'title' => 'Velm',
        'subtitle' => 'Graph',
    ]);

    expect($article->read(['headline', 'score'])[0])
        ->toMatchArray(['headline' => 'Velm: Graph', 'score' => 4]);
});
