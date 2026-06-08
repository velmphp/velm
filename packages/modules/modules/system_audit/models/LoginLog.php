<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class LoginLog extends Model
{
    protected static ?string $name = 'ir.login.log';

    protected static ?string $table = 'ir_login_log';

    public static function defineFields(): array
    {
        return [
            'user_id' => Many2oneField::make('res.users')->label('User'),
            'email' => CharField::make()->label('Email'),
            'event' => CharField::make()->required()->label('Event'),
            'ip_address' => CharField::make()->label('IP address'),
            'user_agent' => CharField::make()->label('User agent'),
            'session_id' => CharField::make()->label('Session'),
            'session_lifetime_minutes' => IntegerField::make()->label('Session lifetime (min)'),
        ];
    }
}
