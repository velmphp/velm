<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Models\Model;

final class Company extends Model
{
    protected static ?string $name = 'res.company';

    protected static ?string $table = 'res_company';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make(required: true),
            'active' => BooleanField::make(default: true),
        ];
    }
}
