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

test('cols below one throws', function (): void {
    FormView::make('x')->model('res.partner')->cols(0);
})->throws(InvalidArgumentException::class);
