<?php

declare(strict_types=1);

namespace Velm\Ui\Tests\Livewire;

use Velm\Ui\Forms\FormMode;

final class WorkflowDefnArchFormProbe extends ArchFormProbe
{
    public FormMode $mode = FormMode::Edit;

    /**
     * @return array<string, mixed>
     */
    protected function arch(): array
    {
        return [
            'title' => 'Workflow',
            'model' => 'workflow.definition',
            'sections' => [[
                'name' => 'main',
                'fields' => [
                    ['name' => 'name'],
                    ['name' => 'group_ids'],
                ],
            ]],
        ];
    }

    protected function listPageUrl(): string
    {
        return '/velm/views/workflow/definition.list';
    }
}
