<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Database\SqlQuote;

test('sql quote uses driver-specific identifier wrapping', function (): void {
    $connection = PdoConnection::sqliteMemory();

    expect(SqlQuote::identifier($connection, 'id'))->toBe('"id"')
        ->and(SqlQuote::identifier($connection, 'res_groups'))->toBe('"res_groups"');
});
