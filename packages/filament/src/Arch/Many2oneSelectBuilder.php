<?php

declare(strict_types=1);

namespace Velm\Filament\Arch;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Velm\Environment;
use Velm\Fields\Many2oneField;
use Velm\Web\Api\Many2oneQuickCreate;
use Velm\Web\Api\Many2oneSearch;

final class Many2oneSelectBuilder
{
    public function __construct(
        private readonly Many2oneSearch $search = new Many2oneSearch,
        private readonly Many2oneQuickCreate $quickCreate = new Many2oneQuickCreate,
    ) {}

    public function make(
        string $name,
        Many2oneField $field,
        Environment $env,
    ): Select {
        $comodel = $field->comodel;
        $label = $field->string ?? $name;

        $select = Select::make($name)
            ->label($label)
            ->searchable()
            ->searchDebounce(300)
            ->nullable()
            ->getSearchResultsUsing(function (?string $search) use ($env, $comodel): array {
                $payload = $this->search->search(
                    $env,
                    $comodel,
                    trim($search ?? ''),
                    20,
                );

                $options = [];
                foreach ($payload['results'] as $row) {
                    $options[(int) $row['id']] = (string) $row['label'];
                }

                return $options;
            })
            ->getOptionLabelUsing(function (mixed $value) use ($env, $comodel): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                $rows = $env->browse($comodel, [(int) $value])->read();

                if ($rows === []) {
                    return null;
                }

                return (string) ($rows[0]['display_name'] ?? $value);
            });

        $modelClass = $env->registry->modelClass($comodel);
        if ($this->quickCreate->canQuickCreate($modelClass)) {
            $select
                ->createOptionForm([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),
                ])
                ->createOptionUsing(function (array $data) use ($env, $comodel): int {
                    $created = $this->quickCreate->create(
                        $env,
                        $comodel,
                        (string) ($data['name'] ?? ''),
                    );

                    return $created['id'];
                });
        }

        if ($field->required === true) {
            $select->required();
        }

        if ($field->readonly === true) {
            $select->disabled();
        }

        return $select;
    }
}
