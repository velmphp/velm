<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Velm\Framework\VelmManager;
use Velm\Modules\ModuleInstaller;

final class AppsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Apps';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $slug = 'apps';

    protected string $view = 'filament-panels::pages.page';

    public function getTitle(): string|Htmlable
    {
        return 'Apps';
    }

    public function table(Table $table): Table
    {
        /** @var list<string> $roots */
        $roots = config('velm.addon_paths', []);

        return $table
            ->records(fn (): array => (new ModuleInstaller)->catalog($roots))
            ->columns([
                TextColumn::make('name')->label('Module')->searchable()->sortable(),
                TextColumn::make('state')->badge(),
                TextColumn::make('version')->label('Version'),
                TextColumn::make('depends')->label('Depends'),
                TextColumn::make('summary')->wrap(),
            ])
            ->recordActions([
                Action::make('install')
                    ->label('Install')
                    ->visible(fn (array $record): bool => $record['state'] === 'uninstalled')
                    ->action(function (array $record) use ($roots): void {
                        app(VelmManager::class)->install($record['name']);

                        Notification::make()
                            ->title("Installed {$record['name']}")
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),
            ]);
    }
}
