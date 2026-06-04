<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Widgets\WidgetContext;
use Velm\Ui\Widgets\WidgetRegistry;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

test('widget registry resolves many2one on new forms', function (): void {
    $env = app(Environment::class);
    $ctx = new WidgetContext(
        $env,
        'res.partner',
        ['name' => 'country_id'],
        FormMode::New,
        [],
    );

    expect((new WidgetRegistry)->resolve($ctx))->toBe('velm-ui::widgets.m2o-input');
});

test('widget registry still resolves char default on new forms', function (): void {
    $env = app(Environment::class);
    $ctx = new WidgetContext(
        $env,
        'res.partner',
        ['name' => 'name'],
        FormMode::New,
        [],
    );

    expect((new WidgetRegistry)->resolve($ctx))->toBe('velm-ui::widgets.char-input');
});

test('widget registry resolves file_url hint for char fields', function (): void {
    $env = app(Environment::class);
    $ctx = new WidgetContext(
        $env,
        'res.company',
        ['name' => 'logo_url', 'widget' => 'file_url'],
        FormMode::Edit,
        [],
    );

    expect((new WidgetRegistry)->resolve($ctx))->toBe('velm-ui::widgets.file-url');
});

test('widget registry resolves rich_text hint for text fields', function (): void {
    $env = app(Environment::class);
    $ctx = new WidgetContext(
        $env,
        'it.change',
        ['name' => 'description', 'widget' => 'rich_text'],
        FormMode::Edit,
        [],
    );

    expect((new WidgetRegistry)->resolve($ctx))->toBe('velm-ui::widgets.rich-text');
});

test('widget registry resolves code hint for text fields', function (): void {
    $env = app(Environment::class);
    $ctx = new WidgetContext(
        $env,
        'workflow.definition',
        ['name' => 'definition', 'widget' => 'code', 'code_language' => 'json'],
        FormMode::Display,
        [],
    );

    expect((new WidgetRegistry)->resolve($ctx))->toBe('velm-ui::widgets.display.code');
});
