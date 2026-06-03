<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Models\Model;

class Article extends Model
{
    protected static ?string $name = 'test.article';

    protected static ?string $table = 'test_article';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'tag_ids' => Many2manyField::make('test.tag')->label('Tags'),
        ];
    }
}
