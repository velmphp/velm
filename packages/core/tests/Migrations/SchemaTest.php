<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Migrations\Schema;
use Velm\Registry;

test('migration schema create and table add columns', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Country::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    $schema = Schema::make($env);

    $schema->create('res_country', static function ($table): void {
        $table->string('name', null, false);
    });

    $schema->table('res_country', static function ($table): void {
        $table->string('code', 2);
    });

    $columns = array_column($connection->fetchAll('PRAGMA table_info("res_country")'), 'name');

    expect($columns)->toContain('name', 'code');
});
