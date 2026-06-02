<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

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

    public function badge(Recordset $records): string
    {
        $base = static::super($records);
        $records->ensureOne();
        $ref = (string) ($records->read()[0]['ref'] ?? '');

        return $ref === '' ? $base : $base.' · '.$ref;
    }
}
