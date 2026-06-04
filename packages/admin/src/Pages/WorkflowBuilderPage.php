<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Concerns\ReconcilesVelmModuleUi;
use Velm\Environment;
use Velm\Modules\Workflow\WorkflowDesigner;

final class WorkflowBuilderPage extends VelmShellPage
{
    use ReconcilesVelmModuleUi;

    /** @var array<string, mixed> */
    public array $config = [];

    public function mount(Environment $env, ?int $workflowId = null): void
    {
        $this->reconcileVelmModuleUi('workflow');

        if ($workflowId === null) {
            $env->checkAccess('workflow.definition', 'create');
            $this->config = WorkflowDesigner::builderConfig($env);

            return;
        }

        $env->checkAccess('workflow.definition', 'write');

        $rows = $env->browse('workflow.definition', [$workflowId])->read();

        if ($rows === []) {
            abort(404, 'Workflow not found');
        }

        $this->config = WorkflowDesigner::builderConfig($env, $rows[0]);
    }

    public function getTitle(): string|Htmlable
    {
        $name = $this->config['meta']['name'] ?? null;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return __('New workflow');
    }

    public function render()
    {
        return view('velm-ui::workflow.builder-page', [
            'config' => $this->config,
        ]);
    }
}
