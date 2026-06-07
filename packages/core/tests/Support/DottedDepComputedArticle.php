<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class DottedDepComputedArticle extends Model
{
    protected static ?string $name = 'test.dotted.dep.article';

    protected static ?string $table = 'test_dotted_dep_article';

    public static function defineFields(): array
    {
        return [
            'title' => CharField::make()->required(),
            'broken' => CharField::make()->compute('computeBroken')->depends('missing.other'),
        ];
    }

    public function computeBroken(): array
    {
        return [];
    }
}
