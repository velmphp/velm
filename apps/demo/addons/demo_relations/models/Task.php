<?php

declare(strict_types=1);

namespace Addons\DemoRelations\Models;

use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class Task extends Model
{
    protected static ?string $name = 'demo.task';

    protected static ?string $table = 'demo_task';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Task'),
            'project_id' => Many2oneField::make('demo.project')
            ->required(false)->label('Project'),
            'cover_id' => Many2oneField::make('ir.attachment')->label('Cover image'),
            'category' => CharField::make()->required(false)->label('Category')
        ];
    }
}
