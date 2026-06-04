<?php

declare(strict_types=1);

namespace Velm\Modules\Mail\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class Follower extends Model
{
    protected static ?string $name = 'mail.follower';

    protected static ?string $table = 'mail_follower';

    public static function defineFields(): array
    {
        return [
            'model' => CharField::make()->required()->label('Related model'),
            'res_id' => IntegerField::make()->required()->label('Related record'),
            'user_id' => Many2oneField::make('res.users')->required()->label('User'),
        ];
    }
}
