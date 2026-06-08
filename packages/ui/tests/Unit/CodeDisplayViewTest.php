<?php

declare(strict_types=1);

use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

test('code display view renders prism markup for json payloads', function (): void {
    $html = view('velm-ui::widgets.display.code', [
        'value' => '{"active": true}',
        'codeLanguage' => 'json',
    ])->render();

    expect($html)
        ->toContain('data-pv-code-display')
        ->toContain('language-json')
        ->toContain('active');
});

test('code display view maps html language alias to prism markup', function (): void {
    $html = view('velm-ui::widgets.display.code', [
        'value' => '<p>Hi</p>',
        'codeLanguage' => 'html',
    ])->render();

    expect($html)->toContain('language-markup');
});

test('code display view renders empty placeholder when value is blank', function (): void {
    $html = view('velm-ui::widgets.display.code', [
        'value' => '',
        'codeLanguage' => 'json',
    ])->render();

    expect($html)->toContain('—')
        ->not->toContain('data-pv-code-display');
});
