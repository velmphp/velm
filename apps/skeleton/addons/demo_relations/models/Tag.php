<?php

declare(strict_types=1);

namespace Addons\DemoRelations\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

class Tag extends Model
{
    protected static ?string $name = 'demo.tag';

    protected static ?string $table = 'demo_tag';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Tag'),
        ];
    }
}
