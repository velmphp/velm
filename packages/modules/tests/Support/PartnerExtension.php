<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Fields\CharField;
use Velm\Modules\Partners\Models\Partner;

class PartnerExtension extends Partner
{
    protected static ?string $inherit = 'res.partner';

    public static function defineFields(): array
    {
        return [
            'ref' => CharField::make()->label('Internal ref'),
        ];
    }

    public static function displayNameFor(array $values): string
    {
        $base = parent::displayNameFor($values);
        $ref = $values['ref'] ?? '';

        if ($ref !== '') {
            return $base.' ('.$ref.')';
        }

        return $base;
    }
}
