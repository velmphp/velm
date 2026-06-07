<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Velm\Admin\Concerns\StoredViewDetailRedirect;

final class DetailRedirectProbe
{
    use StoredViewDetailRedirect;

    public function __construct(
        public ?string $module = null,
        public ?string $viewName = null,
    ) {}

    public function detailUrl(?int $recordId): ?string
    {
        return $this->detailPageUrl($recordId);
    }
}
