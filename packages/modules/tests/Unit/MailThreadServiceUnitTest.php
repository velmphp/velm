<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Modules\Mail\MailThreadService;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('mail thread service registerModel ignores empty model name', function (): void {
    MailThreadService::registerModel('');

    expect(MailThreadService::hasThread(''))->toBeFalse();
});

test('mail thread service threadContext returns null without mail.message model', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);

    $env = $installer->environment($roots);
    $previous = MailThreadService::registeredModels();
    MailThreadService::registerModel('res.partner');

    try {
        expect(MailThreadService::threadContext($env, 'res.partner', 1))->toBeNull();
    } finally {
        MailThreadService::seedRegisteredModelsForTesting($previous);
    }
});

test('mail thread service threadContext returns null when res model read is denied', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $baseEnv = $installer->environment($roots);
    $changeId = $baseEnv->model('it.change')->create([
        'name' => 'Denied read',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $limitedId = $baseEnv->model('res.users')->create([
        'name' => 'No Change Access',
        'email' => 'no-change@example.com',
        'password' => 'secret',
    ])->ids()[0];

    $env = new Environment($baseEnv->connection, $baseEnv->registry, uid: $limitedId);

    expect(MailThreadService::threadContext($env, 'it.change', $changeId))->toBeNull();
});

test('mail thread service postMessage rejects anonymous user', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Anonymous post',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    expect(fn () => MailThreadService::postMessage(
        new Environment($env->connection, $env->registry, uid: null),
        'it.change',
        $changeId,
        'Hello',
    ))->toThrow(AccessDeniedException::class);
});

test('mail thread service postMessage returns fallback row when list lookup misses', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Fallback row',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $posted = MailThreadService::postMessage($env, 'it.change', $changeId, 'notification body', 'notification');

    expect($posted['message_type'])->toBe('notification')
        ->and($posted['author_name'])->not->toBe('');
});

test('mail thread service setFollowing rejects anonymous user', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $baseEnv = $installer->environment($roots);
    $changeId = $baseEnv->model('it.change')->create([
        'name' => 'Follow auth',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $env = new Environment($baseEnv->connection, $baseEnv->registry, uid: null);

    expect(fn () => MailThreadService::setFollowing($env, 'it.change', $changeId, true))
        ->toThrow(AccessDeniedException::class);
});

test('mail thread service bodyToHtml returns empty for empty body', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Empty html',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $env->withAclBypass(function () use ($env, $changeId): void {
        $env->model('mail.message')->create([
            'model' => 'it.change',
            'res_id' => $changeId,
            'body' => '',
            'message_type' => 'comment',
            'author_id' => $env->uid,
        ]);
    });

    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx['messages'][0]['body_html'] ?? null)->toBe('');
});
