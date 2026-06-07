<?php

declare(strict_types=1);

use Velm\Views\Authoring\ViewOp;

test('view op builders produce canonical operation arrays', function (): void {
    $target = ['sections', 'main'];

    expect(ViewOp::remove($target))->toBe(['op' => 'remove', 'target' => $target])
        ->and(ViewOp::set($target, 'x'))->toBe(['op' => 'set', 'target' => $target, 'value' => 'x'])
        ->and(ViewOp::replace($target, 'y'))->toBe(['op' => 'replace', 'target' => $target, 'value' => 'y'])
        ->and(ViewOp::update($target, ['label' => 'Main']))->toBe([
            'op' => 'update',
            'target' => $target,
            'value' => ['label' => 'Main'],
        ])
        ->and(ViewOp::after($target, ['name' => 'z']))->toBe([
            'op' => 'after',
            'target' => $target,
            'value' => ['name' => 'z'],
        ])
        ->and(ViewOp::before($target, ['name' => 'a']))->toBe([
            'op' => 'before',
            'target' => $target,
            'value' => ['name' => 'a'],
        ])
        ->and(ViewOp::append($target, ['name' => 'b']))->toBe([
            'op' => 'append',
            'target' => $target,
            'value' => ['name' => 'b'],
        ])
        ->and(ViewOp::prepend($target, ['name' => 'c']))->toBe([
            'op' => 'prepend',
            'target' => $target,
            'value' => ['name' => 'c'],
        ]);
});
