<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class CountryLegacySuperExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public function greetingLabel(Recordset $records): string
    {
        $base = static::super(__FUNCTION__, $records);

        return 'legacy:'.$base;
    }
}
