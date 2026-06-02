<?php

declare(strict_types=1);

namespace Velm\Filament\Tests\Support;

final class PartnerArch
{
    /**
     * @return array<string, mixed>
     */
    public static function list(): array
    {
        return [
            'view_type' => 'list',
            'model' => 'res.partner',
            'title' => 'Partners',
            'form_view' => 'partner.form',
            'fields' => [
                'name',
                ['name' => 'active', 'widget' => 'toggle'],
                'country_id',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function form(): array
    {
        return [
            'view_type' => 'form',
            'model' => 'res.partner',
            'sections' => [
                [
                    'name' => 'identity',
                    'title' => 'Identity',
                    'fields' => [
                        'name',
                        ['name' => 'active', 'widget' => 'toggle'],
                    ],
                ],
                [
                    'name' => 'address',
                    'title' => 'Address',
                    'fields' => ['country_id'],
                ],
            ],
        ];
    }
}
