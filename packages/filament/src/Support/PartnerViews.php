<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

/**
 * Sample partner view arch used by the Velm panel spike.
 * Production modules will ship equivalent definitions via DATA sync.
 */
final class PartnerViews
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
