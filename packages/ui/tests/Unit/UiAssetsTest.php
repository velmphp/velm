<?php

declare(strict_types=1);

test('built ui assets exist after npm build', function (): void {
    $root = dirname(__DIR__, 2);

    expect(is_file($root.'/resources/css/velm.css'))->toBeTrue()
        ->and(is_file($root.'/resources/js/pv-rich-text.js'))->toBeTrue()
        ->and(is_file($root.'/resources/js/pv-code-editor.js'))->toBeTrue()
        ->and(is_file($root.'/resources/js/flowbite.min.js'))->toBeTrue()
        ->and(is_file($root.'/resources/js/velm-nav.js'))->toBeTrue();
});
