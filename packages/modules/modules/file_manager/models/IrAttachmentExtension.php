<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager\Models;

use Velm\Fields\Many2oneField;
use Velm\Models\Model;

/**
 * Extends {@see ir.attachment} with library folder + company stamp (PyVelm file_manager).
 */
class IrAttachmentExtension extends Model
{
    protected static ?string $inherit = 'ir.attachment';

    public static function defineFields(): array
    {
        return [
            'folder_id' => Many2oneField::make('res.attachment.folder')->label('Folder'),
            'company_id' => Many2oneField::make('res.company')->label('Company'),
        ];
    }
}
