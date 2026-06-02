<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class UiMenu extends Model
{
    protected static ?string $name = 'ir.ui.menu';

    protected static ?string $table = 'ir_ui_menu';

    protected static string $recName = 'label';

    public static function defineFields(): array
    {
        return [
            'module' => CharField::make()->required(),
            'name' => CharField::make()->required(),
            'label' => CharField::make()->required(),
            'parent_id' => Many2oneField::make()->comodel('ir.ui.menu'),
            'sequence' => IntegerField::make()->default(10),
            'href' => CharField::make(),
            'icon' => CharField::make(),
            'active' => BooleanField::make()->default(true),
        ];
    }
}
