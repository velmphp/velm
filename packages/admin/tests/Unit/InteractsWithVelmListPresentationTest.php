<?php

declare(strict_types=1);

use Velm\Admin\Tests\Support\ListPresentationProbe;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('list presentation respects explicit click_to_open arch flag', function (): void {
    $probe = new ListPresentationProbe(['click_to_open' => false], openTarget: true);

    expect($probe->listClickToOpen())->toBeFalse();
});

test('list presentation falls back to open target when click_to_open omitted', function (): void {
    $probe = new ListPresentationProbe([], openTarget: true);

    expect($probe->listClickToOpen())->toBeTrue();
});

test('list presentation filters invalid row actions and appends delete', function (): void {
    $probe = new ListPresentationProbe([
        'model' => 'res.partner',
        'row_actions' => [
            'not-an-array',
            ['action' => '', 'label' => 'Bad'],
            ['action' => 'link', 'label' => 'Docs', 'href' => '/docs/{id}'],
            ['action' => 'open', 'label' => 'Open'],
        ],
    ], openTarget: true, editTarget: true);

    $actions = $probe->listRowActions();

    expect(collect($actions)->pluck('action')->all())
        ->toContain('link', 'open', 'delete')
        ->and($probe->listRowActionUrl(['action' => 'link', 'label' => 'Docs', 'href' => '/docs/{id}', 'icon' => 'x'], 7))
        ->toBe('/docs/7')
        ->and($probe->listRowActionUrl(['action' => 'link', 'label' => 'Plain', 'href' => '/plain', 'icon' => 'x'], 7))
        ->toBe('/plain')
        ->and($probe->listRowActionUrl(['action' => 'link', 'label' => 'Empty', 'href' => '', 'icon' => 'x'], 7))
        ->toBeNull()
        ->and($probe->listRowActionUsesWire(['action' => 'delete', 'label' => 'Delete', 'icon' => 'x']))->toBeTrue();
});

test('list presentation resolves open and edit urls and hides open when disabled', function (): void {
    $probe = new ListPresentationProbe(['click_to_open' => false], openTarget: true, editTarget: true);

    expect($probe->listOpenUrl(5))->toBeNull()
        ->and($probe->listRowActionUrl(['action' => 'open', 'label' => 'Open', 'icon' => 'x'], 5))->toBe('/open/5')
        ->and($probe->listRowActionUrl(['action' => 'edit', 'label' => 'Edit', 'icon' => 'x'], 5))->toBe('/edit/5')
        ->and($probe->hasListRowActions())->toBeFalse();
});

test('list presentation tolerates malformed row action specs', function (): void {
    $probe = new ListPresentationProbe([
        'model' => 'res.partner',
        'row_actions' => 'invalid',
    ]);

    expect($probe->listRowActions())->toBeArray();
});

test('list presentation uses default icons for standard row actions', function (): void {
    $probe = new ListPresentationProbe([
        'model' => 'res.partner',
        'row_actions' => [
            ['action' => 'edit', 'label' => 'Edit'],
            ['action' => 'delete', 'label' => 'Delete'],
        ],
    ], editTarget: true);

    $actions = $probe->listRowActions();

    expect(collect($actions)->firstWhere('action', 'edit')['icon'])->toBe('heroicon-o-pencil-square')
        ->and(collect($actions)->firstWhere('action', 'delete')['icon'])->toBe('heroicon-o-trash');
});

test('list presentation allows actions when model name is empty', function (): void {
    $probe = new ListPresentationProbe([
        'row_actions' => [
            ['action' => 'mystery', 'label' => 'Mystery'],
        ],
    ]);

    expect($probe->listRowActions())->toHaveCount(1);
});
