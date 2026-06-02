<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Velm\Environment;
use Velm\Filament\Arch\ArchTableConfigurator;
use Velm\Filament\Concerns\InteractsWithArchListToolbar;

abstract class ArchListPage extends VelmShellPage implements HasTable
{
    use InteractsWithArchListToolbar;
    use \Filament\Tables\Concerns\InteractsWithTable;

    /**
     * @return array<string, mixed>
     */
    abstract protected static function arch(): array;

    /**
     * @return class-string<ArchCreatePage>|null
     */
    protected static function createPage(): ?string
    {
        return null;
    }

    /**
     * @return class-string<ArchEditPage>|null
     */
    protected static function editPage(): ?string
    {
        return null;
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        $createPage = static::createPage();

        if ($createPage === null) {
            return [];
        }

        return [
            Action::make('create')
                ->label('New')
                ->url($createPage::getUrl()),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        $title = static::arch()['title'] ?? null;

        if (is_string($title) && $title !== '') {
            return $title;
        }

        return parent::getTitle();
    }

    public function table(Table $table): Table
    {
        $env = app(Environment::class);
        $arch = static::arch();

        $table = app(ArchTableConfigurator::class)->configure(
            $table,
            $arch,
            $env,
            fn (): \Velm\Filament\Arch\ListQuery => $this->listQuery(),
        );

        $editPage = static::editPage();

        if ($editPage !== null) {
            $table->recordUrl(
                static fn (array $record): string => $editPage::getUrl(['record' => $record['id']]),
            );
        }

        $table
            ->searchable(false)
            ->columnManager(false)
            ->paginated([10, 20, 50, 100])
            ->defaultPaginationPageOption(10)
            ->deferColumnManager(false);

        if (filled($this->listGroupBy)) {
            $header = $this->findListHeader($this->listGroupBy);

            if ($header !== null) {
                $table->defaultGroup(
                    Group::make($this->listGroupBy)
                        ->label($header['label'])
                        ->collapsible()
                        ->getTitleFromRecordUsing(function (array $record) use ($header, $env): string {
                            $value = $record[$this->listGroupBy] ?? null;

                            if ($value === null || $value === '') {
                                return '—';
                            }

                            if ($header['group_kind'] === 'boolean') {
                                return $value ? 'Yes' : 'No';
                            }

                            if ($header['group_kind'] === 'm2o' && $header['comodel'] !== null) {
                                $rows = $env->browse($header['comodel'], [(int) $value])->read();

                                return (string) ($rows[0]['display_name'] ?? $value);
                            }

                            return (string) $value;
                        }),
                );
            }
        }

        return $table;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('velm-filament::components.arch-list-toolbar'),
            EmbeddedTable::make(),
        ]);
    }
}
