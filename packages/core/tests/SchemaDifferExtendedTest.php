<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Partner;
use Velm\Database\PdoConnection;
use Velm\Registry;
use Velm\Schema\SchemaDiffer;

test('schema differ reports new tables for unregistered models', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $diff = $differ->compute($registry, [Partner::class]);

    expect($diff->newTables)->not->toBeEmpty()
        ->and($diff->newTables[0][0])->toBe('res_partner');
});
