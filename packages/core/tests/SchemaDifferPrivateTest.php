<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Partner;
use Velm\Database\PdoConnection;
use Velm\Registry;
use Velm\Schema\SchemaDiffer;

test('schema differ columnIsNullable treats unknown sqlite columns as nullable', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $differ->apply($registry, [Partner::class]);

    $method = new ReflectionMethod(SchemaDiffer::class, 'columnIsNullable');
    $method->setAccessible(true);

    expect($method->invoke($differ, 'res_partner', 'missing_column'))->toBeTrue();
});

test('schema differ pragmaTableInfo returns empty when pragma fails', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $differ = new SchemaDiffer($connection);

    $method = new ReflectionMethod(SchemaDiffer::class, 'pragmaTableInfo');
    $method->setAccessible(true);

    expect($method->invoke($differ, 'not_a_real_table'))->toBe([])
        ->and($method->invoke($differ, 'bad"quote'))->toBe([]);
});
