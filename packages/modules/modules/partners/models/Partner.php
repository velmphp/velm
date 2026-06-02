<?php

declare(strict_types=1);

namespace Velm\Modules\Partners\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class Partner extends Model
{
    protected static ?string $name = 'res.partner';

    protected static ?string $table = 'res_partner';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'active' => BooleanField::make()->default(true),
            'is_company' => BooleanField::make()->label('Is a company')->default(false),
            'company_id' => Many2oneField::make()->comodel('res.company'),
            'country_id' => Many2oneField::make()->comodel('res.country'),
        ];
    }
}
