<?php

declare(strict_types=1);

use Velm\Admin\Support\CompanyFonts;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('company fonts builds google stylesheet url for curated families', function (): void {
    $url = CompanyFonts::stylesheetUrl('Open Sans');

    expect($url)->toContain('fonts.googleapis.com/css2')
        ->and($url)->toContain('family=Open+Sans');
});

test('company fonts css overrides tailwind font tokens for custom families', function (): void {
    $css = CompanyFonts::css('Roboto');

    expect($css)->toContain('--font-sans: \'Roboto\'')
        ->and($css)->toContain('font-family: \'Roboto\'');
});

test('company fonts context prefers company value over env default', function (): void {
    config(['velm.font_family' => 'Lato']);

    $context = CompanyFonts::contextFromCompanyRow(['font_family' => 'Poppins']);

    expect($context['company_font_family'])->toBe('Poppins')
        ->and($context['company_font_style'])->not->toBe('');
});

test('company fonts context uses inter without css override when unset', function (): void {
    config(['velm.font_family' => null]);
    putenv('VELM_FONT_FAMILY');

    $context = CompanyFonts::contextFromCompanyRow(['font_family' => '']);

    expect($context['company_font_family'])->toBe('Inter')
        ->and($context['company_font_style'])->toBe('');
});
