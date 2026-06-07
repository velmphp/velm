<?php

declare(strict_types=1);

namespace Velm\Ui\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class Document extends Model
{
    protected static ?string $name = 'test.document';

    protected static ?string $table = 'test_document';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'attachment_id' => Many2oneField::make()->comodel('ir.attachment')->label('Attachment'),
            'attachment_ids' => Many2manyField::make('ir.attachment')->label('Attachments'),
        ];
    }
}
