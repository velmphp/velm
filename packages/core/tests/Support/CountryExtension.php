<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class CountryExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'region_code' => CharField::make()->label('Region code')->maxLength(8),
        ];
    }

    public static function displayNameFor(array $values): string
    {
        $base = static::super($values);
        $region = $values['region_code'] ?? '';

        if ($region !== '') {
            return $base.' ['.$region.']';
        }

        return $base;
    }

    public function greetingLabel(Recordset $records): string
    {
        $base = static::super($records);
        $records->ensureOne();
        $region = $records->read()[0]['region_code'] ?? '';

        return $region === '' ? $base : $base.' ('.$region.')';
    }
}
