<?php

declare(strict_types=1);

namespace Addons\ChangeManagement\Dashboard;

use Velm\Environment;

final class ChangesSummaryWidget
{
    /**
     * @return array<string, mixed>
     */
    public static function resolve(Environment $env): array
    {
        $rows = $env->model('it.change')->search(
            order: '"id" DESC',
            limit: 5,
        )->read(['name', 'reference', 'priority', 'change_type']);

        $items = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $name = (string) ($row['name'] ?? '');
            $reference = (string) ($row['reference'] ?? '');

            $items[] = [
                'label' => $reference !== '' ? "{$reference} — {$name}" : $name,
                'meta' => (string) ($row['priority'] ?? ''),
                'sublabel' => (string) ($row['change_type'] ?? ''),
                'href' => $id > 0 ? "/velm/views/change_management/change.detail/{$id}" : null,
            ];
        }

        return [
            'items' => $items,
            'empty_label' => 'No change requests yet.',
            'href' => '/velm/views/change_management/change.list',
            'action_label' => 'All change requests',
        ];
    }
}
