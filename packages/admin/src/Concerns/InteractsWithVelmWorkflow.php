<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Environment;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowService;

trait InteractsWithVelmWorkflow
{
    /**
     * @return array<string, mixed>|null
     */
    public function velmWorkflowContext(): ?array
    {
        $arch = $this->arch();
        $model = (string) ($arch['model'] ?? '');
        $recordId = (int) $this->record;

        if ($model === '' || $recordId <= 0) {
            return null;
        }

        return WorkflowService::formContext(app(Environment::class), $model, $recordId);
    }

    public function velmWorkflowModel(): string
    {
        return (string) ($this->arch()['model'] ?? '');
    }

    public function velmWorkflowRecordId(): int
    {
        return (int) $this->record;
    }

    public function velmWorkflowEnabled(): bool
    {
        $model = $this->velmWorkflowModel();
        $recordId = $this->velmWorkflowRecordId();

        if ($model === '' || $recordId <= 0) {
            return false;
        }

        $env = app(Environment::class);

        if (! $env->registry->has('workflow.instance')) {
            return false;
        }

        if (WorkflowEngine::instanceForRecord($env, $model, $recordId) !== null) {
            return true;
        }

        return WorkflowEngine::activeDefinition($env, $model) !== null;
    }
}
