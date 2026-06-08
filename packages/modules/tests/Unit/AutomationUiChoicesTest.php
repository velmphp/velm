<?php

declare(strict_types=1);

use Velm\Modules\Base\AutomationUiChoices;

test('automation ui choices expose configurable server action types', function (): void {
    $values = collect(AutomationUiChoices::serverActionTypes())->pluck('value')->all();

    expect($values)->toContain('write', 'create', 'unlink', 'workflow_escalate', 'audit_purge')
        ->and(AutomationUiChoices::serverActionTypes()[0])->toHaveKeys(['value', 'label']);
});

test('automation ui choices expose cron interval units', function (): void {
    $values = collect(AutomationUiChoices::intervalTypes())->pluck('value')->all();

    expect($values)->toBe(['minutes', 'hours', 'days', 'weeks']);
});
