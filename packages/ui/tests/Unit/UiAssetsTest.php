<?php

declare(strict_types=1);

use Velm\Ui\Tests\TestCase;
use Velm\Ui\UiAssets;

uses(TestCase::class);

test('ui assets expose built stylesheet and widget script paths', function (): void {
    expect(UiAssets::stylesheetPath())->toEndWith('velm.css')
        ->and(UiAssets::graphScriptPath())->toEndWith('pv-graph.js')
        ->and(UiAssets::pivotScriptPath())->toEndWith('pv-pivot.js');
});

test('ui assets href helpers return asset urls', function (): void {
    expect(UiAssets::stylesheetHref())->toContain('velm.css')
        ->and(UiAssets::graphScriptHref())->toContain('pv-graph.js')
        ->and(UiAssets::pivotScriptHref())->toContain('pv-pivot.js')
        ->and(UiAssets::filesAlpineScriptHref())->toContain('pv-files-alpine.js');
});
