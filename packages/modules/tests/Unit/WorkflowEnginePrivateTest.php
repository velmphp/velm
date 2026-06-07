<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Registry;

test('workflow engine userIdsInGroup returns empty without res.users model', function (): void {
    $method = new ReflectionMethod(WorkflowEngine::class, 'userIdsInGroup');
    $method->setAccessible(true);
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    expect($method->invoke(null, $env, 1))->toBe([]);
});

test('workflow engine splitFormValues ignores unknown form keys', function (): void {
    $method = new ReflectionMethod(WorkflowEngine::class, 'splitFormValues');
    $method->setAccessible(true);

    [$stage, $record] = $method->invoke(null, [
        'form' => [
            'fields' => [
                ['name' => 'note', 'source' => 'stage'],
                ['name' => 'title', 'source' => 'record'],
            ],
        ],
    ], [
        'note' => 'Stage value',
        'title' => 'Record value',
        'unexpected' => 'ignored',
    ]);

    expect($stage)->toBe(['note' => 'Stage value'])
        ->and($record)->toBe(['title' => 'Record value']);
});

test('workflow engine approvalDeadline returns null for invalid hours payload', function (): void {
    $method = new ReflectionMethod(WorkflowEngine::class, 'approvalDeadline');
    $method->setAccessible(true);

    expect($method->invoke(null, ['deadline_hours' => '']))->toBeNull()
        ->and($method->invoke(null, []))->toBeNull();
});
