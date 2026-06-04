<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Modules\Mail\MailThreadService;

trait InteractsWithVelmMailThread
{
    public function velmMailThreadModel(): string
    {
        return (string) ($this->arch()['model'] ?? '');
    }

    public function velmMailThreadRecordId(): int
    {
        return (int) $this->record;
    }

    public function velmMailThreadEnabled(): bool
    {
        $model = $this->velmMailThreadModel();
        $recordId = $this->velmMailThreadRecordId();

        return $model !== '' && $recordId > 0 && MailThreadService::hasThread($model);
    }
}
