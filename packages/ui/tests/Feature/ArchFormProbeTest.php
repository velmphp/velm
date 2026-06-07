<?php

declare(strict_types=1);

use Livewire\Livewire;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Tests\Livewire\ArchFormProbe;
use Velm\Ui\Tests\Livewire\EmptyModelArchFormProbe;
use Velm\Ui\Tests\Livewire\EmbedCloseArchFormProbe;
use Velm\Ui\Tests\Livewire\UserArchFormProbe;
use Velm\Ui\Tests\Livewire\WorkflowDefnArchFormProbe;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('arch form probe saves and deletes partner records', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Probe Delete'])->ids()[0];

    Livewire::test(ArchFormProbe::class, ['record' => $partnerId, 'mode' => FormMode::Edit])
        ->set('data.name', 'Probe Updated')
        ->call('saveVelmForm')
        ->assertHasNoErrors();

    expect(app(\Velm\Environment::class)->browse('res.partner', [$partnerId])->read()[0]['name'])
        ->toBe('Probe Updated');

    Livewire::test(ArchFormProbe::class, ['record' => $partnerId, 'mode' => FormMode::Edit])
        ->call('deleteVelmForm')
        ->assertHasNoErrors();

    expect(app(\Velm\Environment::class)->model('res.partner')->search([['id', '=', $partnerId]])->count())->toBe(0);
});

test('arch form probe surfaces save errors for missing record id', function (): void {
    Livewire::test(ArchFormProbe::class, ['record' => 0, 'mode' => FormMode::Edit])
        ->set('data.name', 'No id')
        ->call('saveVelmForm')
        ->assertSet('formError', fn (?string $msg): bool => is_string($msg) && $msg !== '');
});

test('arch form probe embed mode uses record url and detail redirect target', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Embed Save'])->ids()[0];

    Livewire::withQueryParams(['embed' => '1'])
        ->test(ArchFormProbe::class, [
            'record' => $partnerId,
            'mode' => FormMode::Edit,
            'skipRedirect' => false,
        ])
        ->set('data.name', 'Embed Saved')
        ->call('saveVelmForm')
        ->assertHasNoErrors();
});

test('arch form probe prefill new form values from query string', function (): void {
    $page = Livewire::withQueryParams(['parent_id' => '7', 'note' => 'from-query'])
        ->test(ArchFormProbe::class, ['record' => 0, 'mode' => FormMode::New]);

    expect($page->get('data.parent_id'))->toBe(7)
        ->and($page->get('data.note'))->toBe('from-query');
});

test('arch form probe create mode cannot delete without record', function (): void {
    expect(
        Livewire::test(ArchFormProbe::class, ['record' => 0, 'mode' => FormMode::New])
            ->instance()
            ->velmFormCanDelete(),
    )->toBeFalse();
});

test('arch form probe creates partner records in new mode', function (): void {
    Livewire::test(ArchFormProbe::class, ['record' => 0, 'mode' => FormMode::New])
        ->set('data.name', 'Created Partner')
        ->call('createVelmForm')
        ->assertSet('formError', null);

    $created = app(\Velm\Environment::class)->model('res.partner')->search([['name', '=', 'Created Partner']])->read()[0] ?? [];

    expect($created)->not->toBeEmpty();
});

test('arch form probe redirects to list when submit has no record id', function (): void {
    Livewire::test(ArchFormProbe::class, ['record' => 0, 'mode' => FormMode::New])
        ->call('invokeRedirectAfterSubmit', null)
        ->assertRedirect('/velm/views/partners/partner.list');
});

test('arch form probe exposes form sections and display name helpers', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Display Name'])->ids()[0];

    $page = Livewire::test(ArchFormProbe::class, ['record' => $partnerId, 'mode' => FormMode::Edit]);

    expect($page->instance()->velmFormTitle())->toBe('Partner')
        ->and($page->instance()->velmRecordDisplayName())->toBe('Display Name')
        ->and($page->instance()->velmFormSections())->not->toBeEmpty()
        ->and($page->instance()->velmFormCanDelete())->toBeTrue();
});

test('arch form probe delete in embed mode closes dialog without redirect', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Embed Delete'])->ids()[0];

    Livewire::test(ArchFormProbe::class, [
        'record' => $partnerId,
        'mode' => FormMode::Edit,
        'embedded' => true,
    ])
        ->call('deleteVelmForm')
        ->assertHasNoErrors();

    expect(app(\Velm\Environment::class)->model('res.partner')->search([['id', '=', $partnerId]])->count())->toBe(0);
});

test('arch form probe non-embed save redirects to detail page when enabled', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Detail Redirect'])->ids()[0];

    Livewire::test(ArchFormProbe::class, [
        'record' => $partnerId,
        'mode' => FormMode::Edit,
        'skipRedirect' => false,
    ])
        ->set('data.name', 'Detail Saved')
        ->call('saveVelmForm')
        ->assertRedirect('/velm/views/partners/partner.detail/'.$partnerId);
});

test('arch form probe create surfaces validation errors', function (): void {
    Livewire::test(ArchFormProbe::class, ['record' => 0, 'mode' => FormMode::New])
        ->set('data.name', '')
        ->call('createVelmForm')
        ->assertSet('formError', fn (?string $msg): bool => is_string($msg) && $msg !== '');
});

test('arch form probe embedded save notifies parent and redirects to embed record url', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Embed Notify'])->ids()[0];

    Livewire::test(ArchFormProbe::class, [
        'record' => $partnerId,
        'mode' => FormMode::Edit,
        'skipRedirect' => false,
        'embedded' => true,
    ])
        ->set('data.name', 'Embed Notify Saved')
        ->call('saveVelmForm')
        ->assertRedirect('/velm/views/partners/partner.form/'.$partnerId.'?embed=1');
});

test('arch form probe embedded save keeps existing embed query in record url', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Embed Tagged'])->ids()[0];

    Livewire::test(ArchFormProbe::class, [
        'record' => $partnerId,
        'mode' => FormMode::Edit,
        'skipRedirect' => false,
        'embedded' => true,
        'embedUrlAlreadyTagged' => true,
    ])
        ->set('data.name', 'Embed Tagged Saved')
        ->call('saveVelmForm')
        ->assertRedirect('/velm/views/partners/partner.form/'.$partnerId.'?embed=1');
});

test('arch form probe embedded create closes dialog when no record url is available', function (): void {
    Livewire::test(EmbedCloseArchFormProbe::class, ['record' => 0])
        ->set('data.name', 'Embed Created')
        ->call('createVelmForm')
        ->assertSet('formError', null);
});

test('arch form probe edit mode ignores query prefill values', function (): void {
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'No Prefill'])->ids()[0];

    $page = Livewire::withQueryParams(['name' => 'Ignored'])
        ->test(ArchFormProbe::class, ['record' => $partnerId, 'mode' => FormMode::Edit]);

    expect($page->get('data.name'))->toBe('No Prefill');
});

test('arch form probe empty model display name and delete guards', function (): void {
    $page = Livewire::test(EmptyModelArchFormProbe::class, ['record' => 12, 'mode' => FormMode::Edit]);

    expect($page->instance()->velmRecordDisplayName())->toBe('#12');

    $page->call('deleteVelmForm')->assertSet('formError', null);

    Livewire::test(EmptyModelArchFormProbe::class, ['record' => 0, 'mode' => FormMode::Edit])
        ->call('deleteVelmForm')
        ->assertSet('formError', null);
});

test('arch form probe normalizes and mutates many2many user groups', function (): void {
    app(\Velm\Framework\VelmManager::class)->install('admin');

    $env = app(\Velm\Environment::class);
    $groupId = $env->model('res.groups')->search(limit: 1)->ids()[0];
    $userId = $env->model('res.users')->create([
        'name' => 'M2M User',
        'email' => 'm2m-user-'.uniqid('', true).'@test.local',
        'group_ids' => [$groupId],
    ])->ids()[0];

    $page = Livewire::test(UserArchFormProbe::class, ['record' => $userId, 'mode' => FormMode::Edit]);

    expect($page->get('data.group_ids'))->toBe([$groupId]);

    $page->set('data.group_ids', [(string) $groupId])
        ->call('saveVelmForm')
        ->assertSet('formError', null);
});

test('arch form probe normalizes and mutates many2many workflow groups', function (): void {
    app(\Velm\Framework\VelmManager::class)->install('workflow');

    $env = app(\Velm\Environment::class);
    $groupId = $env->model('res.groups')->search(limit: 1)->ids()[0];
    $defnId = $env->model('workflow.definition')->search(limit: 1)->ids()[0];

    $env->browse('workflow.definition', [$defnId])->write(['group_ids' => [$groupId]]);

    $page = Livewire::test(WorkflowDefnArchFormProbe::class, ['record' => $defnId, 'mode' => FormMode::Edit]);

    expect($page->get('data.group_ids'))->toBe([$groupId]);

    $page->set('data.group_ids', [])
        ->call('saveVelmForm')
        ->assertSet('formError', null);
});
