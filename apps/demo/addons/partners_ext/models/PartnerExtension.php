<?php

declare(strict_types=1);

namespace Addons\PartnersExt\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class PartnerExtension extends Model
{
    protected static ?string $inherit = 'res.partner';

    public static function defineFields(): array
    {
        return [
            'website' => CharField::make()->label('Website'),
        ];
    }
}
