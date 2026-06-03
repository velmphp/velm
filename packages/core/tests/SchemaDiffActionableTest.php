<?php

declare(strict_types=1);

use Velm\Fields\CharField;
use Velm\Schema\SchemaDiff;

test('schema diff is sync actionable for new columns only', function (): void {
    $diff = new SchemaDiff;
    $diff->newColumns[] = ['t', 'code', CharField::make()];

    expect($diff->isSyncActionable(false))->toBeTrue()
        ->and($diff->isSyncActionable(true))->toBeTrue();
});

test('schema diff with only orphan columns is not sync actionable', function (): void {
    $diff = new SchemaDiff;
    $diff->orphanColumns[] = ['t', 'legacy'];

    expect($diff->isSyncActionable(true))->toBeFalse()
        ->and($diff->hasDrift())->toBeTrue();
});

test('schema diff alterations require alter column support', function (): void {
    $diff = new SchemaDiff;
    $diff->alterations[] = new \Velm\Schema\SchemaAlteration('t', 'c', 'drop_not_null', '');

    expect($diff->isSyncActionable(false))->toBeFalse()
        ->and($diff->isSyncActionable(true))->toBeTrue();
});
