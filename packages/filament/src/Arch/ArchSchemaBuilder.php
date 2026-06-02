<?php

declare(strict_types=1);

namespace Velm\Filament\Arch;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\Field;
use Velm\Fields\Many2oneField;

final class ArchSchemaBuilder
{
    /**
     * @param  array<string, mixed>  $arch
     * @return list<Column>
     */
    public function buildTableColumns(array $arch): array
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $columns = [];

        foreach ($arch['fields'] as $field) {
            $columns[] = $this->tableColumnFor($field);
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return list<Component>
     */
    public function buildFormSchema(array $arch, Environment $env): array
    {
        $arch = ArchNormalizer::normalizeForm($arch);
        $model = (string) ($arch['model'] ?? '');
        $components = [];

        foreach ($arch['sections'] as $section) {
            $fields = [];

            foreach ($section['fields'] as $field) {
                $fields[] = $this->formFieldFor($field, $env, $model);
            }

            $components[] = Section::make($section['title'] ?? $section['name'] ?? 'Section')
                ->schema($fields);
        }

        return $components;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function tableColumnFor(array $field): Column
    {
        $name = $field['name'];
        $widget = $field['widget'] ?? null;

        if ($widget === 'toggle') {
            return ToggleColumn::make($name);
        }

        return TextColumn::make($name)
            ->searchable()
            ->sortable();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function formFieldFor(array $field, Environment $env, string $model): Component
    {
        $name = $field['name'];
        $widget = $field['widget'] ?? null;
        $velmField = $this->velmField($env, $model, $name);

        if ($widget === 'toggle' || $velmField instanceof BooleanField) {
            return Toggle::make($name);
        }

        if ($velmField instanceof Many2oneField) {
            return TextInput::make($name)
                ->numeric()
                ->label($velmField->string ?? $name);
        }

        $input = TextInput::make($name);

        if ($velmField?->required === true) {
            $input->required();
        }

        if ($velmField?->readonly === true) {
            $input->disabled();
        }

        return $input;
    }

    private function velmField(Environment $env, string $model, string $name): ?Field
    {
        if ($model === '') {
            return null;
        }

        $modelClass = $env->registry->modelClass($model);

        return $modelClass::fields()[$name] ?? null;
    }
}
