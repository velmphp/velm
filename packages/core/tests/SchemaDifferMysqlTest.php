<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\MysqlSchemaTestConnection;
use Velm\Core\Tests\Support\Partner;
use Velm\Registry;
use Velm\Schema\SchemaDiffer;

test('schema differ apply executes nullability alterations on mysql-capable schema', function (): void {
    $connection = new MysqlSchemaTestConnection;
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $result = $differ->apply($registry, [Partner::class]);

    expect($result->setNotNull)->toBeGreaterThanOrEqual(0)
        ->and($differ->supportsAlterColumnNullability())->toBeTrue();
});

test('schema differ columnIsNullable reads information_schema on mysql driver', function (): void {
    $connection = new MysqlSchemaTestConnection;
    $differ = new SchemaDiffer($connection);

    $method = new ReflectionMethod(SchemaDiffer::class, 'columnIsNullable');
    $method->setAccessible(true);

    expect($method->invoke($differ, 'res_partner', 'name'))->toBeTrue()
        ->and($method->invoke($differ, 'res_partner', 'active'))->toBeFalse()
        ->and($method->invoke($differ, 'res_partner', 'missing'))->toBeTrue();
});
