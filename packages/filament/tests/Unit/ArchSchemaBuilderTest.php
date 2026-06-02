<?php

declare(strict_types=1);

use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Velm\Filament\Arch\ArchNormalizer;
use Velm\Filament\Arch\ArchSchemaBuilder;
use Velm\Filament\Tests\Support\PartnerArch;
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

test('normalizes string field shorthand to field refs', function (): void {
    $arch = ArchNormalizer::normalizeList([
        'fields' => ['name', ['name' => 'active', 'widget' => 'toggle']],
    ]);

    expect($arch['fields'])->toBe([
        ['name' => 'name'],
        ['name' => 'active', 'widget' => 'toggle'],
    ]);
});

test('builds table columns from partner list arch', function (): void {
    $columns = (new ArchSchemaBuilder)->buildTableColumns(PartnerArch::list());

    expect($columns)->toHaveCount(3)
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[1])->toBeInstanceOf(ToggleColumn::class)
        ->and($columns[2])->toBeInstanceOf(TextColumn::class);
});

test('builds form schema from partner form arch', function (): void {
    $schema = (new ArchSchemaBuilder)->buildFormSchema(PartnerArch::form(), $this->env);

    expect($schema)->toHaveCount(2)
        ->and($schema[0])->toBeInstanceOf(Section::class)
        ->and($schema[1])->toBeInstanceOf(Section::class);
});
