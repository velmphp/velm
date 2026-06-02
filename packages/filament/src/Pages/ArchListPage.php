<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Velm\Environment;
use Velm\Filament\Arch\ArchTableConfigurator;

abstract class ArchListPage extends Page implements HasTable
{
    use InteractsWithTable;

    /**
     * @return array<string, mixed>
     */
    abstract protected static function arch(): array;

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
        return app(ArchTableConfigurator::class)->configure(
            $table,
            static::arch(),
            app(Environment::class),
        );
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }
}
