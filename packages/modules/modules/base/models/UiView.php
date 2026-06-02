<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

final class UiView extends Model
{
    protected static ?string $name = 'ir.ui.view';

    protected static ?string $table = 'ir_ui_view';

    protected static string $recName = 'name';

    public static function defineFields(): array
    {
        return [
            'module' => CharField::make()->required(),
            'name' => CharField::make()->required(),
            'model' => CharField::make()->label('Model')->required(),
            'view_type' => CharField::make()->label('View type')->required(),
            'arch' => TextField::make()->label('Arch'),
            'priority' => IntegerField::make()->default(16),
            'inherit_id' => Many2oneField::make()->comodel('ir.ui.view'),
            'operations' => TextField::make()->label('Operations'),
        ];
    }
}
