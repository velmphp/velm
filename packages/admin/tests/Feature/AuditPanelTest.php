<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Modules\ModuleInstaller;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->install('system_audit', $roots);
});

test('logout records email for authenticatable users with attributes', function (): void {
    $user = new AuthenticatableUser;
    $user->forceFill([
        'id' => 1,
        'email' => 'admin@velm.test',
        'password' => 'secret',
    ]);

    $this->actingAs($user);

    $response = $this->post('/'.Velm\Admin\Support\VelmPanel::path().'/logout');

    $response->assertRedirect(route('velm.auth.login'));

    $env = app(Environment::class);
    $rows = $env->model('ir.login.log')->search([['event', '=', 'logout']])->read(['email', 'user_id']);

    expect($rows)->not->toBeEmpty()
        ->and($rows[0]['email'])->toBe('admin@velm.test')
        ->and($rows[0]['user_id'])->toBe(1);
});

test('readonly audit detail page does not expose an edit url', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $env = app(Environment::class);
    $id = $env->withAclBypass(fn () => $env->model('ir.audit.log')->create([
        'name' => 'write res.company#1',
        'action' => 'write',
        'model' => 'res.company',
        'res_id' => 1,
    ])->ids()[0]);

    $component = Livewire::test(StoredViewRecordPage::class, [
        'module' => 'system_audit',
        'viewName' => 'audit_log.detail',
        'record' => $id,
    ]);

    expect($component->instance()->velmEditPageUrl())->toBeNull();
});
