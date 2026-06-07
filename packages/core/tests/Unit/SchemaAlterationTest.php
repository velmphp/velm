<?php

declare(strict_types=1);

use Velm\Fields\CharField;
use Velm\Schema\SchemaAlteration;

test('schema alteration cliLine formats table column and kind', function (): void {
    $alteration = new SchemaAlteration(
        'res_partner',
        'name',
        'set_not_null',
        'SET NOT NULL when no NULL rows remain',
        CharField::make()->required(),
    );

    expect($alteration->cliLine())
        ->toBe('  ~ res_partner.name: set_not_null — SET NOT NULL when no NULL rows remain');
});
