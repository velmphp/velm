<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Models\Model;
use Velm\Recordset\Recordset;

/** Instance method with no parent implementor in the MRO chain. */
final class CountryOnlySuperExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public function soloLabel(Recordset $records): string
    {
        return (string) static::super($records);
    }
}
