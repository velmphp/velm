<?php

declare(strict_types=1);

namespace Velm\Modules\Partners\Dashboard;

use Velm\Environment;

final class PartnersSummaryWidget
{
    /**
     * @return array<string, mixed>
     */
    public static function resolve(Environment $env): array
    {
        $count = $env->model('res.partner')->search()->count();

        return [
            'value' => $count,
            'label' => $count === 1 ? 'contact in your workspace' : 'contacts in your workspace',
            'href' => '/velm/views/partners/partner.list',
            'action_label' => 'Browse contacts',
        ];
    }
}
