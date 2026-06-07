<?php

declare(strict_types=1);

namespace Velm\Ui\Tests\Livewire;

use Velm\Ui\Forms\FormMode;

final class UserArchFormProbe extends ArchFormProbe
{
    public FormMode $mode = FormMode::Edit;

    /**
     * @return array<string, mixed>
     */
    protected function arch(): array
    {
        return [
            'title' => 'User',
            'model' => 'res.users',
            'sections' => [[
                'name' => 'main',
                'fields' => [
                    ['name' => 'name'],
                    ['name' => 'email'],
                    ['name' => 'group_ids'],
                ],
            ]],
        ];
    }

    protected function listPageUrl(): string
    {
        return '/velm/views/base/user.list';
    }
}
