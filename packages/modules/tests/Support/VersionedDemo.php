<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

class VersionedDemo extends Model
{
    protected static ?string $name = 'versioned.demo';

    protected static ?string $table = 'versioned_demo';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
        ];
    }
}
