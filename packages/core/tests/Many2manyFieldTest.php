<?php

declare(strict_types=1);

use Velm\Fields\Many2manyField;

test('many2many field stores relation table metadata', function (): void {
    $field = Many2manyField::make('res.groups')
        ->comodel('res.groups')
        ->relation('res_groups_users_rel', 'uid', 'gid');

    expect($field->comodel)->toBe('res.groups')
        ->and($field->relation)->toBe('res_groups_users_rel')
        ->and($field->column1)->toBe('uid')
        ->and($field->column2)->toBe('gid')
        ->and($field->persistsInDatabase())->toBeFalse();
});

test('many2many field resolveSpec builds default relation names', function (): void {
    $registry = \Velm\Registry::using(function (\Velm\Registry $registry): \Velm\Registry {
        $registry->register(\Velm\Core\Tests\Support\Article::class);
        $registry->register(\Velm\Core\Tests\Support\Tag::class);

        return $registry;
    });

    $field = Many2manyField::make('test.tag');
    [$relation, $col1, $col2] = $field->resolveSpec(\Velm\Core\Tests\Support\Article::class, $registry);

    expect($relation)->toContain('_rel')
        ->and($col1)->toEndWith('_id')
        ->and($col2)->toEndWith('_id');
});

test('many2many field rejects direct sql column access', function (): void {
    expect(fn () => Many2manyField::make('res.groups')->sqlType())
        ->toThrow(LogicException::class);

    expect(fn () => Many2manyField::make('res.groups')->toSql([]))
        ->toThrow(LogicException::class);
});
