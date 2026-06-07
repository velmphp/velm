<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Widgets\WidgetContext;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

test('widget context returns null velm field for empty model', function (): void {
    $env = app(Environment::class);
    $ctx = new WidgetContext(
        $env,
        '',
        ['name' => 'name'],
        FormMode::Edit,
        [],
    );

    expect($ctx->velmField())->toBeNull();
});
