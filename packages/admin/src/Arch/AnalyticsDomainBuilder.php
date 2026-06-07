<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

final class AnalyticsDomainBuilder
{
    /**
     * @param  array<string, mixed>  $arch
     * @return list<mixed>|list<list<mixed>>
     */
    public function build(array $arch): array
    {
        $domain = $arch['domain'] ?? [];

        return is_array($domain) ? $domain : [];
    }
}
