<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Velm\Fields\Many2oneField;
use Velm\Filament\Arch\Many2oneSelectBuilder;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $this->env = $installer->environment($roots);
});

test('many2one select uses search api style results not full table preload', function (): void {
    $this->env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $this->env->model('res.country')->create(['name' => 'Netherlands', 'code' => 'NL']);

    $select = (new Many2oneSelectBuilder)->make(
        'country_id',
        Many2oneField::make(comodel: 'res.country'),
        $this->env,
    );

    expect($select)->toBeInstanceOf(Select::class)
        ->and($select->isSearchable())->toBeTrue();

    $searchCallback = (new ReflectionClass($select))
        ->getProperty('getSearchResultsUsing')
        ->getValue($select);
    expect($searchCallback)->toBeCallable();

    $options = $select->evaluate($searchCallback, ['search' => 'bel']);
    expect($options)->toHaveCount(1)
        ->and($options)->toHaveKey(1)
        ->and($options[1])->toBe('Belgium');

    $labelCallback = (new ReflectionClass($select))
        ->getProperty('getOptionLabelUsing')
        ->getValue($select);
    expect($labelCallback)->toBeCallable()
        ->and($select->evaluate($labelCallback, ['value' => 1]))->toBe('Belgium');
});
