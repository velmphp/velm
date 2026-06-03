<?php

declare(strict_types=1);

use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;

test('detail view declaration uses view_type detail', function (): void {
    $detail = DetailView::make('partner.detail')
        ->model('res.partner')
        ->title('Partner')
        ->section('main', 'Main', ['name'])
        ->toArray();

    expect($detail['view_type'])->toBe('detail')
        ->and($detail['name'])->toBe('partner.detail')
        ->and($detail['arch']['title'])->toBe('Partner')
        ->and($detail['arch']['sections'])->toHaveCount(1);
});

test('list view stores detail_view arch key', function (): void {
    $list = ListView::make('partner.list')
        ->model('res.partner')
        ->formView('partner.form')
        ->detailView('partner.detail')
        ->columns(['name'])
        ->toArray();

    expect($list['arch']['detail_view'])->toBe('partner.detail')
        ->and($list['arch']['form_view'])->toBe('partner.form');
});

test('list view stores click_to_open and explicit row_actions', function (): void {
    $list = ListView::make('partner.list')
        ->model('res.partner')
        ->detailView('partner.detail')
        ->rowActions([ListRowAction::open('View'), ListRowAction::edit()])
        ->columns(['name'])
        ->toArray();

    expect($list['arch']['click_to_open'])->toBeTrue()
        ->and($list['arch']['row_actions'])->toHaveCount(2)
        ->and($list['arch']['row_actions'][0]['action'])->toBe('open')
        ->and($list['arch']['row_actions'][0]['label'])->toBe('View')
        ->and($list['arch']['row_actions'][0]['icon'])->toBe('heroicon-o-eye')
        ->and($list['arch']['row_actions'][1]['icon'])->toBe('heroicon-o-pencil-square');
});

test('list row action supports custom icon', function (): void {
    $action = ListRowAction::open('Preview', 'heroicon-o-folder')->toArray();

    expect($action['icon'])->toBe('heroicon-o-folder');
});

test('list row action delete uses trash icon', function (): void {
    $action = ListRowAction::delete()->toArray();

    expect($action['action'])->toBe('delete')
        ->and($action['label'])->toBe('Delete')
        ->and($action['icon'])->toBe('heroicon-o-trash');
});
