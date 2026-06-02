<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Velm\Environment;
use Velm\Filament\Arch\ArchTableConfigurator;

abstract class ArchListPage extends VelmShellPage implements HasTable
{
    use InteractsWithTable;

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
        $table = app(ArchTableConfigurator::class)->configure(
            $table,
            static::arch(),
            app(Environment::class),
        );

        $editPage = static::editPage();

        if ($editPage !== null) {
            $table->recordUrl(
                static fn (array $record): string => $editPage::getUrl(['record' => $record['id']]),
            );
        }

        return $table;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }
}
