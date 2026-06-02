<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Models\Model;
use Velm\Recordset\Recordset;

/** Wraps the next greetingLabel implementor down the MRO chain. */
final class CountryGreetingTopExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public function greetingLabel(Recordset $records): string
    {
        return '{'.static::super($records).'}';
    }
}
