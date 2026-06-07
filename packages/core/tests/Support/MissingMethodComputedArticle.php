<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Models\Model;

final class MissingMethodComputedArticle extends Model
{
    protected static ?string $name = 'test.missing.method.article';

    protected static ?string $table = 'test_missing_method_article';

    public static function defineFields(): array
    {
        return [
            'title' => CharField::make()->required(),
            'broken' => CharField::make()->compute('missingMethod')->depends('title'),
        ];
    }
}
