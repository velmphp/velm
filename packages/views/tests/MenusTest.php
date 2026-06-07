<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;

test('menus helper builds module scoped parent references', function (): void {
    $menus = new Menus('partners');

    expect($menus->parentRef('business'))->toBe('partners.business')
        ->and($menus->parent('admin', 'settings'))->toBe('admin.settings')
        ->and($menus->group('business', 'Business')->flatten()[0]['name'])->toBe('business')
        ->and($menus->item('partners', 'Partners')->toArray()['name'])->toBe('partners');
});
