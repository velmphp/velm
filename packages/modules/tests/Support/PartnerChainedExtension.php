<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class PartnerChainedExtension extends Model
{
    protected static ?string $inherit = 'res.partner';

    public static function defineFields(): array
    {
        return [
            'chain_tag' => CharField::make()->label('Chain tag'),
        ];
    }

    public static function displayNameFor(array $values): string
    {
        $base = static::super($values);
        $tag = (string) ($values['chain_tag'] ?? '');

        return $tag === '' ? $base : $base.' #'.$tag;
    }
}
