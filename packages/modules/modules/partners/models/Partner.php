<?php

declare(strict_types=1);

namespace Velm\Modules\Partners\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

class Partner extends Model
{
    protected static ?string $name = 'res.partner';

    protected static ?string $table = 'res_partner';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'active' => BooleanField::make()->default(true)->label('Active'),
            'is_company' => BooleanField::make()->label('Is a company')->default(false),
            'company_id' => Many2oneField::make()->comodel('res.company')->label('Company'),
            'country_id' => Many2oneField::make()->comodel('res.country')->label('Country'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function schemaExternalColumns(): array
    {
        return ['ref'];
    }

    public function badge(Recordset $records): string
    {
        $records->ensureOne();

        return (string) ($records->read()[0]['name'] ?? '');
    }
}
