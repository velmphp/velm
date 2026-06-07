<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class ExternalColumnPartner extends Model
{
    protected static ?string $name = 'res.external.partner';

    protected static ?string $table = 'res_external_partner';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function schemaExternalColumns(): array
    {
        return ['legacy_ref'];
    }
}
