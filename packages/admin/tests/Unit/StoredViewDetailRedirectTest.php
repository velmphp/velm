<?php

declare(strict_types=1);

use Velm\Admin\Tests\Support\DetailRedirectProbe;
use Velm\Admin\Tests\Support\VelmViewDetailRedirectProbe;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('detail redirect returns null for invalid record ids', function (): void {
    $probe = new DetailRedirectProbe('partners', 'partner.form');

    expect($probe->detailUrl(null))->toBeNull()
        ->and($probe->detailUrl(0))->toBeNull();
});

test('detail redirect returns null without module or view name', function (): void {
    expect((new DetailRedirectProbe(null, 'partner.form'))->detailUrl(1))->toBeNull()
        ->and((new DetailRedirectProbe('partners', null))->detailUrl(1))->toBeNull();
});

test('detail redirect resolves partner form to detail page url', function (): void {
    $url = (new DetailRedirectProbe('partners', 'partner.form'))->detailUrl(42);

    expect($url)->toBeString()
        ->and($url)->toContain('partners')
        ->and($url)->toContain('partner.detail')
        ->and($url)->toContain('42');
});

test('detail redirect resolves module and view via velmView methods', function (): void {
    $url = (new VelmViewDetailRedirectProbe)->detailUrl(9);

    expect($url)->toContain('partner.detail/9');
});
