<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Database\PdoConnection;
use Velm\Fields\CharField;
use Velm\Schema\LaravelSchema;

test('char fields with defaults use varchar columns for mysql compatibility', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);

    $mimetype = CharField::make()->default('application/octet-stream')->bind('mimetype');
    $name = CharField::make()->required()->bind('name');

    $schema->createModelTable('ir_attachment', [$mimetype, $name]);

    $rows = $connection->fetchAll('PRAGMA table_info("ir_attachment")');
    $types = [];

    foreach ($rows as $row) {
        $types[(string) $row['name']] = strtolower((string) $row['type']);
    }

    expect($types['mimetype'])->toBe('varchar')
        ->and($types['name'])->toBe('text');
});

test('explicit char max length is preserved', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);

    $schema->createModelTable('res_country', Country::fields());

    expect($schema->columnListing('res_country'))->toContain('code');
});
