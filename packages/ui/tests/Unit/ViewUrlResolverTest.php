<?php

declare(strict_types=1);

use Velm\Ui\Support\ViewUrlResolver;

test('record and edit hrefs follow stored view slug shape', function (): void {
    $base = '/velm/views/demo_relations/tag.form';

    expect(ViewUrlResolver::recordHref($base, 3))
        ->toBe('/velm/views/demo_relations/tag.form/3')
        ->and(ViewUrlResolver::recordEditHref($base, 3))
        ->toBe('/velm/views/demo_relations/tag.form/3/edit')
        ->and(ViewUrlResolver::createHref($base))
        ->toBe('/velm/views/demo_relations/tag.form/create');
});
