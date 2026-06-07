<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class CyclicComputedArticle extends Model
{
    protected static ?string $name = 'test.cyclic.article';

    protected static ?string $table = 'test_cyclic_article';

    public static function defineFields(): array
    {
        return [
            'seed' => IntegerField::make()->default(1),
            'alpha' => IntegerField::make()->compute('computeAlpha')->depends('beta')->stored(),
            'beta' => IntegerField::make()->compute('computeBeta')->depends('alpha')->stored(),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function computeAlpha(Recordset $records): array
    {
        return [];
    }

    /**
     * @return array<int, int>
     */
    public function computeBeta(Recordset $records): array
    {
        return [];
    }
}
