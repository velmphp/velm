<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

/**
 * UI choices for automation settings (server actions and scheduled jobs).
 */
final class AutomationUiChoices
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function serverActionTypes(): array
    {
        return [
            ['value' => 'write', 'label' => 'Update records'],
            ['value' => 'create', 'label' => 'Create record'],
            ['value' => 'unlink', 'label' => 'Delete records'],
            ['value' => 'workflow_escalate', 'label' => 'Workflow — escalate overdue approvals'],
            ['value' => 'audit_purge', 'label' => 'Audit — purge old log rows'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function intervalTypes(): array
    {
        return [
            ['value' => 'minutes', 'label' => 'Minutes'],
            ['value' => 'hours', 'label' => 'Hours'],
            ['value' => 'days', 'label' => 'Days'],
            ['value' => 'weeks', 'label' => 'Weeks'],
        ];
    }
}
