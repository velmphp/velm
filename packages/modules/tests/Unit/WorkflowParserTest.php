<?php

declare(strict_types=1);

use Velm\Modules\Workflow\WorkflowParser;

test('workflow parser parse accepts arrays unchanged', function (): void {
    $raw = ['model' => 'res.partner', 'states' => []];

    expect(WorkflowParser::parse($raw))->toBe($raw);
});

test('workflow parser parse decodes json strings', function (): void {
    $decoded = WorkflowParser::parse('{"model":"res.partner","auto_start":true}');

    expect($decoded['model'])->toBe('res.partner')
        ->and($decoded['auto_start'])->toBeTrue();
});

test('workflow parser parse returns empty array for invalid json', function (): void {
    expect(WorkflowParser::parse('not-json'))->toBe([]);
});

test('workflow parser loadJson handles null empty and array inputs', function (): void {
    expect(WorkflowParser::loadJson(null))->toBe([])
        ->and(WorkflowParser::loadJson(''))->toBe([])
        ->and(WorkflowParser::loadJson(false))->toBe([])
        ->and(WorkflowParser::loadJson(['x' => 1]))->toBe(['x' => 1]);
});

test('workflow parser loadJson decodes json strings', function (): void {
    expect(WorkflowParser::loadJson('{"version":1}'))->toBe(['version' => 1]);
});
