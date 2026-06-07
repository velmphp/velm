<?php

declare(strict_types=1);

namespace Addons\DemoRelations\Models;

use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;

class Project extends Model
{
    protected static ?string $name = 'demo.project';

    protected static ?string $table = 'demo_project';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Project'),
            'tag_ids' => Many2manyField::make('demo.tag')->label('Tags'),
            'document_ids' => Many2manyField::make('ir.attachment')->label('Documents'),
            'task_ids' => One2manyField::make('demo.task', 'project_id')->label('Tasks'),
        ];
    }
}
