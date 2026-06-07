<?php

declare(strict_types=1);

use Velm\Views\Authoring\ViewTarget;

test('view target builds section and field paths', function (): void {
    expect(ViewTarget::section('identity')->fields()->segments())->toBe([
        'sections', 'identity', 'fields',
    ])->and(ViewTarget::section('identity')->field('name')->segments())->toBe([
        'sections', 'identity', 'fields', 'name',
    ]);
});

test('view target parses dot paths', function (): void {
    expect(ViewTarget::path('sections.identity.fields.name')->segments())->toBe([
        'sections', 'identity', 'fields', 'name',
    ]);
});

test('view target rejects empty dot paths', function (): void {
    ViewTarget::path('');
})->throws(InvalidArgumentException::class, 'must not be empty');

test('view target append adds trailing segments', function (): void {
    expect(ViewTarget::section('identity')->append('fields', 'name')->segments())->toBe([
        'sections', 'identity', 'fields', 'name',
    ]);
});

test('view target exposes cols and column helpers', function (): void {
    expect(ViewTarget::cols()->segments())->toBe(['cols'])
        ->and(ViewTarget::column('email')->segments())->toBe(['fields', 'email']);
});
