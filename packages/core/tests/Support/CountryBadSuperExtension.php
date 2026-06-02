<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class CountryBadSuperExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public function callsSuperWithoutRecordset(Recordset $records): string
    {
        return (string) static::super();
    }
}
