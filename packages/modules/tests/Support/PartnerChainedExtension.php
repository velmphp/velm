<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Fields\CharField;

class PartnerChainedExtension extends PartnerExtension
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
        $base = parent::displayNameFor($values);
        $tag = (string) ($values['chain_tag'] ?? '');

        return $tag === '' ? $base : $base.' #'.$tag;
    }
}
