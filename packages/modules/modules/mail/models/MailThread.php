<?php

declare(strict_types=1);

namespace Velm\Modules\Mail\Models;

use Velm\Models\Model;

/**
 * Abstract mixin — compose onto models with {@code protected static array $mixins = ['mail.thread'];}.
 */
abstract class MailThread extends Model
{
    protected static ?string $name = 'mail.thread';

    protected static bool $abstract = true;

    protected static bool $timestamps = false;
}
