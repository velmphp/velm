<?php

declare(strict_types=1);

use Velm\Admin\Support\CompanyTheme;

test('company theme generates css from primary color', function (): void {
    $css = CompanyTheme::css('#6366f1');

    expect($css)->toContain('--color-primary-600: #6366f1')
        ->and($css)->toContain('--color-fg-brand');
});

test('invalid primary color yields empty theme css', function (): void {
    expect(CompanyTheme::css('not-a-color'))->toBe('');
});
