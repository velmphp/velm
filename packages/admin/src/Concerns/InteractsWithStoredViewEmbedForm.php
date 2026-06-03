<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Admin\Support\StoredViewRoutes;

trait InteractsWithStoredViewEmbedForm
{
    protected function velmFormEmbedRecordUrl(int $recordId): ?string
    {
        return StoredViewRoutes::recordPageUrl($this->module, $this->viewName, $recordId);
    }
}
