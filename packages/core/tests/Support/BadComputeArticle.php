<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class BadComputeArticle extends Model
{
    protected static ?string $name = 'test.bad.compute';

    protected static ?string $table = 'test_bad_compute';

    public static function defineFields(): array
    {
        return [
            'title' => CharField::make()->required(),
            'broken' => CharField::make()->compute('computeBroken')->depends('title'),
            'unstored' => CharField::make()->compute('computeUnstored')->depends('title'),
        ];
    }

    public function computeBroken(Recordset $records): string
    {
        return 'not-an-array';
    }

    /**
     * @return array<int, string>
     */
    public function computeUnstored(Recordset $records): array
    {
        $values = [];

        foreach ($records->read(['title']) as $row) {
            $values[(int) $row['id']] = (string) ($row['title'] ?? '');
        }

        return $values;
    }
}
