<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Fields\CharField;
use Velm\Modules\Partners\Models\Partner;

class PartnerIndependentExtension extends Partner
{
    protected static ?string $inherit = 'res.partner';

    public static function defineFields(): array
    {
        return [
            'independent_note' => CharField::make()->label('Independent note'),
        ];
    }

    public static function displayNameFor(array $values): string
    {
        $base = parent::displayNameFor($values);
        $note = (string) ($values['independent_note'] ?? '');

        return $note === '' ? $base : $base.' {'.$note.'}';
    }
}
