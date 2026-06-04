<?php

declare(strict_types=1);

namespace Velm\Modules\Mail\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

final class Message extends Model
{
    protected static ?string $name = 'mail.message';

    protected static ?string $table = 'mail_message';

    public static function defineFields(): array
    {
        return [
            'model' => CharField::make()->required()->label('Related model'),
            'res_id' => IntegerField::make()->required()->label('Related record'),
            'body' => TextField::make()->required()->label('Body'),
            'message_type' => CharField::make()->default('comment')->label('Type'),
            'subject' => CharField::make()->label('Subject'),
            'author_id' => Many2oneField::make('res.users')->label('Author'),
        ];
    }
}
