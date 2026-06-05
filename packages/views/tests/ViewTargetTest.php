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
