<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Environment;
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
}
