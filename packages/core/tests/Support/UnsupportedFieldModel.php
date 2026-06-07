<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\Field;
use Velm\Models\Model;

final class UnsupportedFieldModel extends Model
{
    protected static ?string $name = 'test.unsupported';

    protected static ?string $table = 'test_unsupported';

    public static function defineFields(): array
    {
        return [
            'custom' => new class('custom') extends Field {
                public function sqlType(): string
                {
                    return 'CUSTOM';
                }
            },
        ];
    }
}
