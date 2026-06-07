<?php

declare(strict_types=1);

use Velm\Migrations\ColumnDefinition;
use Velm\Migrations\Table;

test('migration table blueprint collects column definitions', function (): void {
    $table = (new Table)
        ->id()
        ->string('code', 2, false)
        ->string('note')
        ->text('body', false)
        ->integer('qty')
        ->boolean('active', true, false);

    $columns = $table->columns();

    expect($columns)->toHaveCount(5)
        ->and($columns[0])->toEqual(new ColumnDefinition('code', 'VARCHAR(2)', false))
        ->and($columns[1])->toEqual(new ColumnDefinition('note', 'TEXT', true))
        ->and($columns[2])->toEqual(new ColumnDefinition('body', 'TEXT', false))
        ->and($columns[3])->toEqual(new ColumnDefinition('qty', 'INTEGER', true))
        ->and($columns[4])->toEqual(new ColumnDefinition('active', 'BOOLEAN', true, false));
});

test('migration table dropColumn is not auto-applied', function (): void {
    (new Table)->dropColumn('legacy');
})->throws(RuntimeException::class, 'dropColumn');
