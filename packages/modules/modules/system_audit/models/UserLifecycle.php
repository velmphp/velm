<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit\Models;

use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

final class UserLifecycle extends Model
{
    protected static ?string $name = 'ir.user.lifecycle';

    protected static ?string $table = 'ir_user_lifecycle';

    public static function defineFields(): array
    {
        return [
            'user_id' => Many2oneField::make('res.users')->required()->label('User'),
            'event' => CharField::make()->required()->label('Event'),
            'detail' => TextField::make()->label('Detail'),
            'actor_id' => Many2oneField::make('res.users')->label('Actor'),
        ];
    }
}
