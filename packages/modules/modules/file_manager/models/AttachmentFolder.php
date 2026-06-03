<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class AttachmentFolder extends Model
{
    protected static ?string $name = 'res.attachment.folder';

    protected static ?string $table = 'res_attachment_folder';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'parent_id' => Many2oneField::make('res.attachment.folder')->label('Parent'),
            'sequence' => IntegerField::make()->default(10)->label('Sequence'),
            'color' => CharField::make()->label('Color'),
            'company_id' => Many2oneField::make('res.company')->label('Company'),
        ];
    }
}
