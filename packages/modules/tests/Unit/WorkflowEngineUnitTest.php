<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Modules\Workflow\WorkflowDefinitionError;
use Velm\Modules\Workflow\WorkflowEngine;

test('workflow engine transition ui exposes form metadata', function (): void {
    $ui = WorkflowEngine::transitionUi([
        'key' => 'submit',
        'label' => 'Submit',
        'kind' => 'approval',
        'form' => [
            'title' => 'Review',
            'fields' => [['name' => 'note', 'type' => 'text']],
        ],
    ]);

    expect($ui['key'])->toBe('submit')
        ->and($ui['form_title'])->toBe('Review')
        ->and($ui['form_fields'])->toHaveCount(1);
});

test('workflow engine user may trigger rejects automatic transitions for normal users', function (): void {
    expect(WorkflowEngine::userMayTrigger(['kind' => 'automatic'], 2))->toBeFalse()
        ->and(WorkflowEngine::userMayTrigger(['kind' => 'user'], 2))->toBeTrue()
        ->and(WorkflowEngine::userMayTrigger(['kind' => 'automatic'], Environment::SUPERUSER_ID))->toBeTrue();
});

test('workflow engine initial state throws when definition lacks initial state', function (): void {
    expect(fn () => WorkflowEngine::initialState(['states' => [['key' => 'draft']], 'transitions' => []]))
        ->toThrow(WorkflowDefinitionError::class);
});

test('workflow engine transition by key throws for unknown key', function (): void {
    expect(fn () => WorkflowEngine::transitionByKey(['transitions' => []], 'missing'))
        ->toThrow(WorkflowDefinitionError::class);
});
