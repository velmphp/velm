<?php

declare(strict_types=1);

use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Framework\VelmManager;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('base module registers automation list and form views', function (): void {
    $env = app(Environment::class);
    $registry = new ViewRegistry;

    $serverList = $registry->arch($env, 'base', 'server.action.list');
    $cronForm = $registry->arch($env, 'base', 'cron.form');

    expect($serverList['model'])->toBe('ir.actions.server')
        ->and($serverList['form_view'])->toBe('server.action.form')
        ->and($serverList['detail_view'])->toBe('server.action.detail')
        ->and($cronForm['model'])->toBe('ir.cron')
        ->and(collect($cronForm['sections'][1]['fields'])->pluck('name'))->toContain('interval_number', 'interval_type');
});

test('admin module sync adds automation menu items', function (): void {
    app(VelmManager::class)->install('admin');
    $env = app(Environment::class);

    expect($env->model('ir.ui.menu')->search([['name', '=', 'settings.automation']])->count())->toBe(1)
        ->and($env->model('ir.ui.menu')->search([['name', '=', 'settings.server_actions']])->count())->toBe(1)
        ->and($env->model('ir.ui.menu')->search([['name', '=', 'settings.scheduled_actions']])->count())->toBe(1);
});
