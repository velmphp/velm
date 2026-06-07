<?php

declare(strict_types=1);

namespace Velm\Ui\Tests\Livewire;

use Velm\Ui\Forms\FormMode;

final class EmptyModelArchFormProbe extends ArchFormProbe
{
    public FormMode $mode = FormMode::Edit;

    public function mount(int $record = 0): void
    {
        $this->record = $record;
    }

    /**
     * @return array<string, mixed>
     */
    protected function arch(): array
    {
        return [
            'title' => 'Untitled',
            'model' => '',
            'sections' => [[
                'name' => 'main',
                'fields' => [['name' => 'name']],
            ]],
        ];
    }
}
