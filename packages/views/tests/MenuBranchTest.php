<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

test('group children assigns parent and flattens for sync', function (): void {
    $m = new Menus('partners');

    $menus = ViewsData::make()
        ->menus(
            $m->group('business', 'Business')->icon('home')->sequence(50)
                ->children(
                    $m->group('business.directory', 'Directory')->sequence(10)
                        ->children(
                            $m->item('business.partners', 'Partners')
                                ->view('partner.list')
                                ->sequence(10),
                        ),
                ),
        )
        ->toArray()['MENUS'];

    $byName = array_column($menus, null, 'name');

    expect($menus)->toHaveCount(3)
        ->and($byName['business']['parent'] ?? null)->toBeNull()
        ->and($byName['business.directory']['parent'])->toBe('partners.business')
        ->and($byName['business.partners']['parent'])->toBe('partners.business.directory')
        ->and($byName['business.partners']['href'])->toBe('/velm/views/partners/partner.list');
});

test('explicit parent on item is not overwritten by children', function (): void {
    $m = new Menus('partners');

    $menus = ViewsData::make()
        ->menus(
            $m->group('business', 'Business')
                ->children(
                    $m->item('business.tags', 'Tags')
                        ->parent('admin', 'settings.reference')
                        ->view('tag.list'),
                ),
        )
        ->toArray()['MENUS'];

    $tags = collect($menus)->firstWhere('name', 'business.tags');

    expect($tags['parent'])->toBe('admin.settings.reference');
});

test('nested groups flatten in depth-first order for sync', function (): void {
    $m = new Menus('admin');
    $built = ViewsData::make()
        ->menus(
            $m->group('settings', 'Settings')->sequence(1)
                ->children(
                    $m->group('settings.organization', 'Organization')->sequence(5)
                        ->children(
                            $m->item('settings.companies', 'Companies')->sequence(10)
                                ->view('company.list'),
                        ),
                ),
        )
        ->toArray()['MENUS'];

    expect(array_column($built, 'name'))->toBe([
        'settings',
        'settings.organization',
        'settings.companies',
    ]);
});
