<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Partner;
use Velm\Database\PdoConnection;
use Velm\Registry;
use Velm\Schema\SchemaDiffer;

test('schema differ apply runs extension inherit column diff branch', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);
        $registry->registerExtension(\Velm\Modules\Tests\Support\PartnerExtension::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $result = $differ->apply($registry, [Partner::class, \Velm\Modules\Tests\Support\PartnerExtension::class]);

    expect($result->diff->newTables)->not->toBeEmpty();
});
