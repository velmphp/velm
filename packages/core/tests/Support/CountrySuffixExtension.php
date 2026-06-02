<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class CountrySuffixExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'suffix' => CharField::make()->maxLength(8),
        ];
    }

    public function labelWithSuffix(Recordset $records, string $separator = '!'): string
    {
        $records->ensureOne();
        $row = $records->read()[0];
        $name = (string) ($row['name'] ?? '');
        $suffix = (string) ($row['suffix'] ?? '');

        return $suffix === '' ? $name : $name.$separator.$suffix;
    }
}
