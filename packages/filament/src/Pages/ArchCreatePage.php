<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Velm\Environment;
use Velm\Filament\Arch\ArchFormConfigurator;

abstract class ArchCreatePage extends VelmShellPage
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @return array<string, mixed>
     */
    abstract protected static function arch(): array;

    /**
     * @return class-string<ArchListPage>
     */
    abstract protected static function listPage(): string;

    public function getTitle(): string|Htmlable
    {
        $title = static::arch()['title'] ?? 'Record';

        return 'New '.$title;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function create(): void
    {
        $state = $this->mutateFormData($this->form->getState());
        $arch = static::arch();
        $model = (string) $arch['model'];

        app(Environment::class)->model($model)->create($state);

        Notification::make()
            ->success()
            ->title('Created')
            ->send();

        $this->redirect(static::listPage()::getUrl());
    }

    public function form(Schema $schema): Schema
    {
        return app(ArchFormConfigurator::class)->configure(
            $schema,
            static::arch(),
            app(Environment::class),
        );
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('create')
                ->footer([
                    Actions::make($this->getFormActions())->key('form-actions'),
                ]),
        ]);
    }

    /**
     * @return list<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->submit('create'),
            Action::make('cancel')
                ->label('Cancel')
                ->url(static::listPage()::getUrl())
                ->color('gray'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormData(array $data): array
    {
        unset($data['id'], $data['display_name']);

        return $data;
    }
}
