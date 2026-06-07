<?php

declare(strict_types=1);

use Velm\Environment;
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

test('mail thread service threadContext is readonly when mail message read is denied', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $baseEnv = $installer->environment($roots);
    $userGroupId = $baseEnv->model('res.groups')->search([['name', '=', 'User']], limit: 1)->ids()[0];

    $readerId = $baseEnv->model('res.users')->create([
        'name' => 'Mail Reader Limited',
        'email' => 'mail-reader@example.com',
        'password' => 'secret',
        'group_ids' => [$userGroupId],
    ])->ids()[0];

    $baseEnv->withAclBypass(function () use ($baseEnv, $userGroupId): void {
        $baseEnv->model('ir.model.access')->search([
            ['model', '=', 'mail.message'],
            ['group_id', '=', $userGroupId],
        ])->unlink();
    });

    $changeId = $baseEnv->model('it.change')->create([
        'name' => 'Readonly mail',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $env = new Environment($baseEnv->connection, $baseEnv->registry, uid: $readerId);
    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx)->not->toBeNull()
        ->and($ctx['readonly'])->toBeTrue()
        ->and($ctx['can_post'])->toBeFalse()
        ->and($ctx['messages'])->toBe([]);
});

test('mail thread service threadContext disables posting when create access is denied', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $baseEnv = $installer->environment($roots);
    $userGroupId = $baseEnv->model('res.groups')->search([['name', '=', 'User']], limit: 1)->ids()[0];

    $posterId = $baseEnv->model('res.users')->create([
        'name' => 'Mail Create Denied',
        'email' => 'mail-create-denied@example.com',
        'password' => 'secret',
        'group_ids' => [$userGroupId],
    ])->ids()[0];

    $baseEnv->withAclBypass(function () use ($baseEnv, $userGroupId): void {
        $access = $baseEnv->model('ir.model.access')->search([
            ['model', '=', 'mail.message'],
            ['group_id', '=', $userGroupId],
        ]);
        $access->write(['perm_create' => false]);
    });

    $changeId = $baseEnv->model('it.change')->create([
        'name' => 'No post',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $env = new Environment($baseEnv->connection, $baseEnv->registry, uid: $posterId);
    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx)->not->toBeNull()
        ->and($ctx['readonly'])->toBeFalse()
        ->and($ctx['can_post'])->toBeFalse();
});

test('mail thread service setFollowing rejects unsupported models', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);

    $env = $installer->environment($roots);

    expect(fn () => MailThreadService::setFollowing($env, 'res.partner', 1, true))
        ->toThrow(InvalidArgumentException::class, 'does not support mail.thread');
});

test('mail thread service listMessages uses numeric author id when user row is missing', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Missing author',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $ghostAuthorId = 424242;

    $env->withAclBypass(function () use ($env, $changeId, $ghostAuthorId): void {
        $env->model('mail.message')->create([
            'model' => 'it.change',
            'res_id' => $changeId,
            'body' => 'From ghost',
            'message_type' => 'comment',
            'author_id' => $ghostAuthorId,
        ]);
    });

    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx['messages'][0]['author_name'])->toBe((string) $ghostAuthorId);
});

test('mail thread service setFollowing is idempotent when already following', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Already following',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    MailThreadService::setFollowing($env, 'it.change', $changeId, true);
    MailThreadService::setFollowing($env, 'it.change', $changeId, true);

    expect(MailThreadService::threadContext($env, 'it.change', $changeId)['follower_count'])->toBe(1);
});

test('mail thread service listMessages handles many2one author_id tuples', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Tuple author',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $env->withAclBypass(function () use ($env, $changeId): void {
        $env->model('mail.message')->create([
            'model' => 'it.change',
            'res_id' => $changeId,
            'body' => 'Tuple author message',
            'message_type' => 'comment',
            'author_id' => $env->uid,
        ]);
    });

    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx['messages'][0]['author_id'])->toBe($env->uid);
});
