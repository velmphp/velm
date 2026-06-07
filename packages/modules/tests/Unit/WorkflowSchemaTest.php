<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Partner;
use Velm\Modules\Workflow\WorkflowDefinitionError;
use Velm\Modules\Workflow\WorkflowSchema;
use Velm\Registry;

test('workflow schema validates minimal partner definition', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [],
    ], $registry);

    expect(true)->toBeTrue();
});

test('workflow schema rejects missing model', function (): void {
    $registry = Registry::using(fn (Registry $r): Registry => $r);

    WorkflowSchema::validate([
        'version' => 1,
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects unknown model name', function (): void {
    $registry = Registry::using(fn (Registry $r): Registry => $r);

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'missing.model',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects empty definition', function (): void {
    $registry = Registry::using(fn (Registry $r): Registry => $r);

    WorkflowSchema::validate([], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects missing initial state', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft'],
            ['key' => 'done', 'label' => 'Done'],
        ],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects invalid transition kind and approval strategy', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [[
            'key' => 'bad',
            'label' => 'Bad',
            'from' => ['draft'],
            'to' => 'done',
            'kind' => 'robot',
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema validates approval transition form fields', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [[
            'key' => 'submit',
            'label' => 'Submit',
            'from' => ['draft'],
            'to' => 'done',
            'kind' => 'approval',
            'approval' => ['strategy' => 'sequential', 'assignee_type' => 'group'],
            'form' => [
                'fields' => [
                    ['name' => 'name', 'source' => 'record'],
                    ['name' => 'note', 'type' => 'text', 'source' => 'stage'],
                ],
            ],
        ]],
    ], $registry);

    expect(true)->toBeTrue();
});

test('workflow schema rejects invalid transition from state reference', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['missing'],
            'to' => 'done',
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects duplicate transition keys', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [
            ['key' => 'go', 'label' => 'Go', 'from' => ['draft'], 'to' => 'done'],
            ['key' => 'go', 'label' => 'Again', 'from' => ['draft'], 'to' => 'done'],
        ],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects invalid form field source and type', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['draft'],
            'to' => 'done',
            'form' => [
                'fields' => [
                    ['name' => 'note', 'source' => 'invalid'],
                ],
            ],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects duplicate form field names', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['draft'],
            'to' => 'done',
            'form' => [
                'fields' => [
                    ['name' => 'note', 'type' => 'text'],
                    ['name' => 'note', 'type' => 'text'],
                ],
            ],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects duplicate state keys', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'draft', 'label' => 'Again'],
        ],
    ], $registry);
})->throws(WorkflowDefinitionError::class);
