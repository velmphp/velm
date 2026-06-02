<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

class Country extends Model
{
    protected static ?string $name = 'res.country';

    protected static ?string $table = 'res_country';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'code' => CharField::make()->maxLength(2),
        ];
    }

    public function greetingLabel(Recordset $records): string
    {
        $records->ensureOne();
        $row = $records->read()[0];

        return 'Hello '.(string) ($row['name'] ?? '');
    }
}
