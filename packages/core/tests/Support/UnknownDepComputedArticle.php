<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class UnknownDepComputedArticle extends Model
{
    protected static ?string $name = 'test.unknown.dep.article';

    protected static ?string $table = 'test_unknown_dep_article';

    public static function defineFields(): array
    {
        return [
            'title' => CharField::make()->required(),
            'broken' => CharField::make()->compute('computeBroken')->depends('missing_field'),
        ];
    }

    public function computeBroken(): array
    {
        return [];
    }
}
