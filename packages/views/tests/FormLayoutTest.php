<?php

declare(strict_types=1);

use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;

test('form view stores default and section column counts', function (): void {
    $form = FormView::make('partner.form')
        ->model('res.partner')
        ->cols(3)
        ->section('main', 'Main', ['name'], cols: 2)
        ->toArray();

    expect($form['arch']['cols'])->toBe(3)
        ->and($form['arch']['sections'][0]['cols'])->toBe(2);
});

test('detail view stores column layout arch', function (): void {
    $detail = DetailView::make('partner.detail')
        ->model('res.partner')
        ->cols(2)
        ->section('body', 'Body', [
            Field::make('name')->colspan(2),
            Field::make('active')->toggle(),
        ])
        ->toArray();

    expect($detail['arch']['cols'])->toBe(2)
        ->and($detail['arch']['sections'][0]['fields'][0]['colspan'])->toBe(2)
        ->and($detail['arch']['sections'][0]['fields'][1]['widget'])->toBe('toggle');
});

test('field colspan full maps to wide', function (): void {
    $field = Field::make('notes')->colspan('full')->toArray();

    expect($field)->toMatchArray([
        'name' => 'notes',
        'wide' => true,
    ])
        ->and($field)->not->toHaveKey('colspan');
});

test('field accept stores mime filter and invalid colspan throws', function (): void {
    expect(Field::make('logo')->accept('image/*')->toArray()['accept'])->toBe('image/*');

    Field::make('notes')->colspan(0);
})->throws(InvalidArgumentException::class, 'colspan must be at least 1');

test('cols below one throws', function (): void {
    FormView::make('x')->model('res.partner')->cols(0);
})->throws(InvalidArgumentException::class);

test('form view notebook stores pages and field declarations', function (): void {
    $form = FormView::make('partner.form')
        ->model('res.partner')
        ->notebook('tabs', 'Tabs', [
            [
                'name' => 'main',
                'title' => 'Main',
                'fields' => [Field::make('name'), 'active'],
                'cols' => 2,
            ],
            [
                'name' => 'extra',
                'fields' => ['website'],
            ],
        ], cols: 1)
        ->toArray();

    expect($form['arch']['sections'][0]['pages'])->toHaveCount(2)
        ->and($form['arch']['sections'][0]['pages'][0]['fields'][0])->toMatchArray(['name' => 'name'])
        ->and($form['arch']['sections'][0]['pages'][0]['fields'][1])->toBe(['name' => 'active'])
        ->and($form['arch']['sections'][0]['cols'])->toBe(1);
});

test('section cols below one throws', function (): void {
    FormView::make('x')->model('res.partner')->section('main', 'Main', ['name'], cols: 0);
})->throws(InvalidArgumentException::class, 'Section cols');

test('notebook cols below one throws', function (): void {
    FormView::make('x')->model('res.partner')->notebook('tabs', 'Tabs', [['name' => 'a', 'fields' => []]], cols: 0);
})->throws(InvalidArgumentException::class, 'Notebook cols');
