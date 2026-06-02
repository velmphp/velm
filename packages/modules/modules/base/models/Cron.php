<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class Cron extends Model
{
    protected static ?string $name = 'ir.cron';

    protected static ?string $table = 'ir_cron';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'action_id' => Many2oneField::make('ir.actions.server')->label('Server action'),
            'interval_number' => IntegerField::make()->default(1)->label('Interval'),
            'interval_type' => CharField::make()->default('hours')->label('Unit'),
            'nextcall' => CharField::make()->label('Next call (UTC)'),
            'lastcall' => CharField::make()->label('Last call (UTC)'),
            'active' => BooleanField::make()->default(true)->label('Active'),
        ];
    }
}
