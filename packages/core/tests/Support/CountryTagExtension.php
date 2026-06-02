<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class CountryTagExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'tag' => CharField::make()->maxLength(32),
        ];
    }

    public static function displayNameFor(array $values): string
    {
        $base = static::super($values);
        $tag = $values['tag'] ?? '';

        if ($tag !== '') {
            return $base.' #'.$tag;
        }

        return $base;
    }
}
