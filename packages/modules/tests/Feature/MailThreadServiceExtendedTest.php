<?php

declare(strict_types=1);

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

test('mail thread service registers and exposes thread models', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);

    expect(MailThreadService::hasThread('it.change'))->toBeTrue()
        ->and(MailThreadService::registeredModels())->toHaveKey('it.change');
});

test('mail thread context returns messages and follower state', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);

    $changeId = $env->model('it.change')->create([
        'name' => 'Mail context change',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    MailThreadService::postMessage($env, 'it.change', $changeId, 'Hello thread');
    MailThreadService::setFollowing($env, 'it.change', $changeId, true);

    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx)->not->toBeNull()
        ->and($ctx['following'])->toBeTrue()
        ->and($ctx['messages'])->not->toBeEmpty();
});

test('mail thread service unfollow clears follower state', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Follow toggle',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    MailThreadService::setFollowing($env, 'it.change', $changeId, true);
    MailThreadService::setFollowing($env, 'it.change', $changeId, false);

    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx['following'])->toBeFalse()
        ->and($ctx['follower_count'])->toBe(0);
});

test('mail thread service returns null for unknown model', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);

    $env = $installer->environment($roots);

    expect(MailThreadService::threadContext($env, 'res.partner', 1))->toBeNull();
});

test('mail thread service postMessage rejects empty body and unknown model', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Mail validation',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    expect(fn () => MailThreadService::postMessage($env, 'res.partner', 1, 'Hello'))
        ->toThrow(InvalidArgumentException::class, 'does not support mail.thread');

    expect(fn () => MailThreadService::postMessage($env, 'it.change', $changeId, '   '))
        ->toThrow(InvalidArgumentException::class, 'body is required');
});

test('mail thread service bodyToHtml escapes plain text and preserves html', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'HTML body',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $plain = MailThreadService::postMessage($env, 'it.change', $changeId, "line1\nline2");
    $html = MailThreadService::postMessage($env, 'it.change', $changeId, '<p>rich</p>');

    expect($plain['body_html'])->toContain('line1')
        ->and($html['body_html'])->toBe('<p>rich</p>');
});
