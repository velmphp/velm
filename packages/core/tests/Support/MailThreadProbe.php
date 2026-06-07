<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class MailThreadProbe extends Model
{
    protected static ?string $name = 'test.mail.thread';

    protected static ?string $table = 'test_mail_thread';

    protected static bool $mailThread = true;

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
        ];
    }
}
