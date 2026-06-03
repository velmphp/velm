<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Environment;
use Velm\Admin\Support\VelmNotify;
use Velm\Ui\Concerns\InteractsWithVelmArchForm;
use Velm\Ui\Forms\FormMode;

abstract class ArchEditPage extends VelmShellPage
{
    use InteractsWithVelmArchForm;

    public int|string $record = 0;

    /**
     * @return array<string, mixed>
     */
    abstract protected function arch(): array;

    abstract protected function listPageUrl(): string;

    protected function velmFormMode(): FormMode
    {
        return FormMode::Edit;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edit '.$this->velmFormTitle();
    }

    public function mount(int|string $record): void
    {
        $this->record = (int) $record;
        $this->fillVelmFormFromRecord($this->record);
    }

    protected function afterVelmFormSaved(): void
    {
        VelmNotify::flash('success', 'Saved');
    }

    public function render()
    {
        return view('velm-ui::form.page');
    }
}
