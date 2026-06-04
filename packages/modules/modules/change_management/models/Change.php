<?php

declare(strict_types=1);

namespace Velm\Modules\ChangeManagement\Models;

use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class Change extends Model
{
    protected static ?string $name = 'it.change';

    protected static ?string $table = 'it_change';

    protected static bool $mailThread = true;

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Title'),
            'reference' => CharField::make()->label('Reference'),
            'description' => TextField::make()->label('Description'),
            'change_type' => CharField::make()->default('normal')->label('Change type'),
            'priority' => CharField::make()->default('normal')->label('Priority'),
            'risk_level' => CharField::make()->label('Risk level'),
            'business_justification' => TextField::make()->label('Business justification'),
            'implementation_notes' => TextField::make()->label('Implementation notes'),
            'requester_id' => Many2oneField::make('res.users')->label('Requester'),
            'implementer_id' => Many2oneField::make('res.users')->label('Implementer'),
            'planned_start' => DatetimeField::make()->label('Planned start'),
            'planned_end' => DatetimeField::make()->label('Planned end'),
            'company_id' => Many2oneField::make('res.company')->label('Company'),
        ];
    }
}
