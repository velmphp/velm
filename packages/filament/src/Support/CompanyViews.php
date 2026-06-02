<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

final class CompanyViews
{
    /**
     * @return array<string, mixed>
     */
    public static function list(): array
    {
        return [
            'view_type' => 'list',
            'model' => 'res.company',
            'title' => 'Companies',
            'form_view' => 'company.form',
            'fields' => [
                'name',
                ['name' => 'active', 'widget' => 'toggle'],
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
            'model' => 'res.company',
            'sections' => [
                [
                    'name' => 'main',
                    'title' => 'Company',
                    'fields' => [
                        'name',
                        ['name' => 'active', 'widget' => 'toggle'],
                    ],
                ],
            ],
        ];
    }
}
