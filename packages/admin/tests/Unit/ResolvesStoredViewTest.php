<?php

declare(strict_types=1);

use Velm\Admin\Tests\Support\ArchListProbe;
use Velm\Admin\Tests\Support\ResolvesStoredViewProbe;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('resolves stored view returns null form and edit targets when readonly', function (): void {
    $probe = new ArchListProbe;
    $probe->probeArch = [
        'model' => 'res.partner',
        'readonly' => true,
        'form_view' => 'partner.form',
        'edit_view' => 'partner.form',
    ];

    expect($probe->exposeListFormViewName())->toBeNull()
        ->and($probe->exposeListEditViewName())->toBeNull()
        ->and($probe->exposeCreatePageUrl())->toBeNull()
        ->and($probe->exposeSupportsRecordEdit())->toBeFalse();
});

test('resolves stored view derives detail view from form view name', function (): void {
    $probe = new ArchListProbe;
    $probe->probeArch = [
        'model' => 'res.partner',
        'form_view' => 'partner.form',
    ];

    expect($probe->exposeListDetailViewName())->toBe('partner.detail');
});

test('resolves stored view returns null record urls without detail or edit views', function (): void {
    $probe = new ArchListProbe;
    $probe->probeArch = [
        'model' => 'res.partner',
        'form_view' => 'partner_readonly',
    ];

    expect($probe->exposeOpenRecordUrl(7))->toBeNull()
        ->and($probe->exposeEditRecordUrl(7))->toBeNull()
        ->and($probe->exposeSupportsRecordOpen())->toBeFalse();
});

test('resolves stored view builds create and record urls when views are configured', function (): void {
    $probe = new ResolvesStoredViewProbe;
    $probe->probeArch = [
        'model' => 'res.partner',
        'form_view' => 'partner.form',
        'detail_view' => 'partner.detail',
        'edit_view' => 'partner.form',
    ];

    expect($probe->exposeCreatePageUrl())->toContain('partner.form')
        ->and($probe->exposeOpenRecordUrl(12))->toContain('partner.detail')
        ->and($probe->exposeEditRecordUrl(12))->toContain('partner.form')
        ->and($probe->exposeSupportsRecordOpen())->toBeTrue()
        ->and($probe->exposeListHasEditTarget())->toBeTrue();
});
