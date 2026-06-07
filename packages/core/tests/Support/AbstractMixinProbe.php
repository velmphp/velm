<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Models\Model;

abstract class AbstractMixinProbe extends Model
{
    protected static ?string $name = 'test.mixin';

    protected static bool $abstract = true;

    public static function defineFields(): array
    {
        return [];
    }
}
