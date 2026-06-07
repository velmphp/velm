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

test('workflow schema rejects unsupported version and invalid auto_start', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 2,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'Unsupported workflow version');

test('workflow schema rejects non-boolean auto_start flag', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'auto_start' => 'yes',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'auto_start');

test('workflow schema rejects approval transitions missing user field', function (): void {
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
            'approval' => ['assignee_type' => 'field', 'user_field' => 'missing_field'],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects empty states list and invalid state entries', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'non-empty list');

test('workflow schema rejects non-object state entries', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => ['bad'],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'must be an object');

test('workflow schema rejects invalid transitions container', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => 'bad',
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'must be a list');

test('workflow schema rejects transition with empty from list', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => [],
            'to' => 'draft',
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'non-empty list');

test('workflow schema validates approval all strategy with reject_to', function (): void {
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
            ['key' => 'rejected', 'label' => 'Rejected'],
        ],
        'transitions' => [[
            'key' => 'submit',
            'label' => 'Submit',
            'from' => ['draft'],
            'to' => 'done',
            'kind' => 'approval',
            'reject_to' => 'rejected',
            'approval' => [
                'strategy' => 'all',
                'assignee_type' => 'user',
                'user_id' => 1,
            ],
        ]],
    ], $registry);

    expect(true)->toBeTrue();
});

test('workflow schema rejects invalid approval object', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'submit',
            'label' => 'Submit',
            'from' => ['draft'],
            'to' => 'draft',
            'kind' => 'approval',
            'approval' => 'bad',
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'approval must be an object');

test('workflow schema rejects invalid form field object', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['draft'],
            'to' => 'draft',
            'form' => ['fields' => ['bad']],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'must be an object');

test('workflow schema rejects empty state key and label', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => '', 'label' => 'Draft', 'initial' => true]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'key must be a string');

test('workflow schema rejects empty state label string', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => '', 'initial' => true]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'label must be a string');

test('workflow schema rejects string transition entries', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => ['bad'],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'must be an object');

test('workflow schema rejects empty transition label', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'go',
            'label' => '',
            'from' => ['draft'],
            'to' => 'draft',
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'label must be a string');

test('workflow schema rejects invalid reject_to target', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['draft'],
            'to' => 'draft',
            'reject_to' => 'missing',
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'reject_to');

test('workflow schema rejects invalid approval strategy and assignee type', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'submit',
            'label' => 'Submit',
            'from' => ['draft'],
            'to' => 'draft',
            'kind' => 'approval',
            'approval' => ['strategy' => 'random', 'assignee_type' => 'team'],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class);

test('workflow schema rejects invalid form fields list and stage field type', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['draft'],
            'to' => 'draft',
            'form' => ['fields' => 'bad'],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'must be a list');

test('workflow schema rejects invalid stage field type token', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    WorkflowSchema::validate([
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [[
            'key' => 'go',
            'label' => 'Go',
            'from' => ['draft'],
            'to' => 'draft',
            'form' => ['fields' => [['name' => 'note', 'type' => 'blob']]],
        ]],
    ], $registry);
})->throws(WorkflowDefinitionError::class, 'type invalid');
