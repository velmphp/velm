<?php

declare(strict_types=1);

use Velm\Modules\Mail\MailThreadService;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('mail thread model flag registers it.change and messages persist', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);

    expect(MailThreadService::hasThread('it.change'))->toBeTrue();

    $changeId = $env->model('it.change')->create([
        'name' => 'Mail thread test change',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    $posted = MailThreadService::postMessage($env, 'it.change', $changeId, 'First log note');

    expect($posted['body'])->toBe('First log note');

    $ctx = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx)->not->toBeNull()
        ->and($ctx['messages'])->toHaveCount(1)
        ->and($ctx['messages'][0]['body'])->toBe('First log note');

    MailThreadService::setFollowing($env, 'it.change', $changeId, true);

    $ctx2 = MailThreadService::threadContext($env, 'it.change', $changeId);

    expect($ctx2['following'])->toBeTrue()
        ->and($ctx2['follower_count'])->toBe(1);
});
