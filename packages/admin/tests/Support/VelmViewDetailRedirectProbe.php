<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Velm\Admin\Concerns\StoredViewDetailRedirect;

final class VelmViewDetailRedirectProbe
{
    use StoredViewDetailRedirect;

    public function detailUrl(?int $recordId): ?string
    {
        return $this->detailPageUrl($recordId);
    }

    public function velmViewModule(): string
    {
        return 'partners';
    }

    public function velmViewName(): string
    {
        return 'partner.form';
    }
}
