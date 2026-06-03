<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Article;
use Velm\Core\Tests\Support\BadOrder;
use Velm\Core\Tests\Support\Order;
use Velm\Core\Tests\Support\OrderLine;
use Velm\Core\Tests\Support\Tag;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

function relationTestEnvironment(): Environment
{
    return Registry::using(function (Registry $registry): Environment {
        $registry->register(Tag::class);
        $registry->register(Article::class);
        $registry->register(OrderLine::class);
        $registry->register(Order::class);

        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry, Environment::SUPERUSER_ID);
    });
}

test('many2many read and write on parent', function (): void {
    $env = relationTestEnvironment();
    $red = $env->model('test.tag')->create(['name' => 'Red']);
    $blue = $env->model('test.tag')->create(['name' => 'Blue']);
    $article = $env->model('test.article')->create([
        'name' => 'News',
        'tag_ids' => [$red->ids()[0], $blue->ids()[0]],
    ]);

    expect($article->read(['tag_ids'])[0]['tag_ids'])->toBe([$red->ids()[0], $blue->ids()[0]]);

    $article->write(['tag_ids' => [$red->ids()[0]]]);

    expect($article->read(['tag_ids'])[0]['tag_ids'])->toBe([$red->ids()[0]]);
});

test('one2many read returns linked child ids', function (): void {
    $env = relationTestEnvironment();
    $order = $env->model('test.order')->create(['name' => 'SO001']);
    $line1 = $env->model('test.order.line')->create([
        'order_id' => $order->ids()[0],
        'description' => 'Item A',
    ]);
    $line2 = $env->model('test.order.line')->create([
        'order_id' => $order->ids()[0],
        'description' => 'Item B',
    ]);
    $env->model('test.order.line')->create(['description' => 'Orphan']);

    $read = $order->read(['line_ids'])[0]['line_ids'];

    expect($read)->toBe([$line1->ids()[0], $line2->ids()[0]]);
});

test('one2many write replaces linked children', function (): void {
    $env = relationTestEnvironment();
    $order = $env->model('test.order')->create(['name' => 'SO002']);
    $keep = $env->model('test.order.line')->create([
        'order_id' => $order->ids()[0],
        'description' => 'Keep',
    ]);
    $drop = $env->model('test.order.line')->create([
        'order_id' => $order->ids()[0],
        'description' => 'Drop',
    ]);
    $add = $env->model('test.order.line')->create(['description' => 'Add']);

    $order->write(['line_ids' => [$keep->ids()[0], $add->ids()[0]]]);

    expect($order->read(['line_ids'])[0]['line_ids'])->toBe([$keep->ids()[0], $add->ids()[0]])
        ->and($drop->read(['order_id'])[0]['order_id'])->toBeNull();
});

test('one2many validates inverse many2one at registration', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(OrderLine::class);
        $registry->register(BadOrder::class);
    }))->toThrow(\RuntimeException::class);
});

test('schema sync creates many2many relation table', function (): void {
    $env = relationTestEnvironment();
    $rows = $env->connection->fetchAll(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='test_article_test_tag_rel'",
    );

    expect($rows)->not->toBeEmpty();
});
