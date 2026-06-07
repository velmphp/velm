<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class ComputedArticle extends Model
{
    protected static ?string $name = 'test.article';

    protected static ?string $table = 'test_article';

    public static function defineFields(): array
    {
        return [
            'title' => CharField::make()->required(),
            'subtitle' => CharField::make(),
            'headline' => CharField::make()
                ->compute('computeHeadline')
                ->depends('title', 'subtitle'),
            'score' => IntegerField::make()
                ->compute('computeScore')
                ->depends('title')
                ->stored(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function computeHeadline(Recordset $records): array
    {
        $values = [];

        foreach ($records->read(['title', 'subtitle']) as $row) {
            $title = (string) ($row['title'] ?? '');
            $subtitle = (string) ($row['subtitle'] ?? '');
            $values[(int) $row['id']] = trim($subtitle !== '' ? "{$title}: {$subtitle}" : $title);
        }

        return $values;
    }

    /**
     * @return array<int, int>
     */
    public function computeScore(Recordset $records): array
    {
        $values = [];

        foreach ($records->read(['title']) as $row) {
            $values[(int) $row['id']] = strlen((string) ($row['title'] ?? ''));
        }

        return $values;
    }
}
