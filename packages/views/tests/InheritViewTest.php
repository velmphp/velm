<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\Section;
use Velm\Views\Authoring\ViewTarget;

test('inherit view fluent builder serializes extends and operations', function (): void {
    $inherit = InheritView::make('partner.form.ext')
        ->extends('partners.partner.form')
        ->priority(25)
        ->setCols(2)
        ->updateSection('identity', title: 'Contact')
        ->removeSection('address');

    expect($inherit->toArray())->toBe([
        'name' => 'partner.form.ext',
        'inherit' => 'partners.partner.form',
        'operations' => [
            ['op' => 'set', 'target' => ['cols'], 'value' => 2],
            ['op' => 'update', 'target' => ['sections', 'identity'], 'value' => ['title' => 'Contact']],
            ['op' => 'remove', 'target' => ['sections', 'address']],
        ],
        'priority' => 25,
    ]);
});

test('inherit view section helpers build after and section payloads', function (): void {
    $inherit = InheritView::make('partner.form.ext')
        ->extends('partners.partner.form')
        ->updateSection('identity', title: 'Contact', cols: 2)
        ->afterField('identity', 'name', Field::make('website'))
        ->afterSection('identity', Section::make('location', 'Location')->cols(2)->fields('company_id'));

    expect($inherit->toArray()['operations'])->toBe([
        ['op' => 'update', 'target' => ['sections', 'identity'], 'value' => ['title' => 'Contact', 'cols' => 2]],
        ['op' => 'after', 'target' => ['sections', 'identity', 'fields', 'name'], 'value' => ['name' => 'website']],
        ['op' => 'after', 'target' => ['sections', 'identity'], 'value' => [
            'name' => 'location',
            'title' => 'Location',
            'fields' => [['name' => 'company_id']],
            'cols' => 2,
        ]],
    ]);
});

test('inherit view append and prepend in section', function (): void {
    $inherit = InheritView::make('partner.form.ext')
        ->extends('partners.partner.form')
        ->prependInSection('identity', Field::make('code'))
        ->appendInSection('identity', 'notes', Field::make('website'));

    expect($inherit->toArray()['operations'])->toBe([
        ['op' => 'prepend', 'target' => ['sections', 'identity', 'fields'], 'value' => ['name' => 'code']],
        ['op' => 'append', 'target' => ['sections', 'identity', 'fields'], 'value' => ['name' => 'notes']],
        ['op' => 'append', 'target' => ['sections', 'identity', 'fields'], 'value' => ['name' => 'website']],
    ]);
});

test('inherit view accepts dot paths and view targets', function (): void {
    $inherit = InheritView::make('partner.list.ext')
        ->extends('partners.partner.list')
        ->update('fields.name', ['label' => 'Partner name'])
        ->after(ViewTarget::column('email'), Field::make('phone'));

    expect($inherit->toArray()['operations'])->toBe([
        ['op' => 'update', 'target' => ['fields', 'name'], 'value' => ['label' => 'Partner name']],
        ['op' => 'after', 'target' => ['fields', 'email'], 'value' => ['name' => 'phone']],
    ]);
});

test('inherit view accepts bulk operations array', function (): void {
    $inherit = InheritView::make('partner.list.ext')
        ->inherit('partners.partner.list')
        ->operations([
            ['op' => 'update', 'target' => ['fields', 'name'], 'value' => ['label' => 'Partner name']],
        ]);

    expect($inherit->toArray()['operations'])->toHaveCount(1);
});

test('inherit view requires extends before serialize', function (): void {
    InheritView::make('orphan')->toArray();
})->throws(LogicException::class);

test('inherit view rejects empty name and parent ref', function (): void {
    InheritView::make('');
})->throws(InvalidArgumentException::class, 'name must not be empty');

test('inherit view rejects empty extends target', function (): void {
    InheritView::make('x')->extends('');
})->throws(InvalidArgumentException::class, 'parent ref must not be empty');

test('inherit view rejects non-array bulk operations', function (): void {
    InheritView::make('partner.list.ext')
        ->extends('partners.partner.list')
        ->operations(['not-an-array']);
})->throws(InvalidArgumentException::class, 'must be an array');

test('inherit view supports before replace remove column and generic targets', function (): void {
    $inherit = InheritView::make('partner.form.ext')
        ->extends('partners.partner.form')
        ->beforeField('identity', 'name', Field::make('ref'))
        ->beforeSection('identity', Section::make('lead', 'Lead')->fields('title'))
        ->removeColumn('website')
        ->replace(ViewTarget::section('identity'), Section::make('identity', 'Identity')->fields('name'))
        ->append('sections.identity.fields', Field::make('phone'))
        ->prepend('sections.identity.fields', Field::make('code'));

    $ops = $inherit->toArray()['operations'];

    expect($ops)->toEqual([
        ['op' => 'before', 'target' => ['sections', 'identity', 'fields', 'name'], 'value' => ['name' => 'ref']],
        ['op' => 'before', 'target' => ['sections', 'identity'], 'value' => [
            'name' => 'lead',
            'title' => 'Lead',
            'fields' => [['name' => 'title']],
        ]],
        ['op' => 'remove', 'target' => ['fields', 'website']],
        ['op' => 'replace', 'target' => ['sections', 'identity'], 'value' => [
            'name' => 'identity',
            'title' => 'Identity',
            'fields' => [['name' => 'name']],
        ]],
        ['op' => 'append', 'target' => ['sections', 'identity', 'fields'], 'value' => ['name' => 'phone']],
        ['op' => 'prepend', 'target' => ['sections', 'identity', 'fields'], 'value' => ['name' => 'code']],
    ]);
});

test('inherit view accepts raw target arrays', function (): void {
    $inherit = InheritView::make('partner.list.ext')
        ->extends('partners.partner.list')
        ->update(['fields', 'name'], ['label' => 'Partner']);

    expect($inherit->toArray()['operations'][0]['target'])->toBe(['fields', 'name']);
});
