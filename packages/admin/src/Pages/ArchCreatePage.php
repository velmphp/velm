<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Support\VelmNotify;
use Velm\Ui\Concerns\InteractsWithVelmArchForm;
use Velm\Ui\Forms\FormMode;

abstract class ArchCreatePage extends VelmShellPage
{
    use InteractsWithVelmArchForm;

    /**
     * @return array<string, mixed>
     */
    abstract protected function arch(): array;

    abstract protected function listPageUrl(): string;

    protected function velmFormMode(): FormMode
    {
        return FormMode::New;
    }

    public function getTitle(): string|Htmlable
    {
        return 'New '.$this->velmFormTitle();
    }

    public function mount(): void
    {
        $this->resetVelmForm();
    }

    protected function afterVelmFormCreated(): void
    {
        VelmNotify::flash('success', 'Created');
    }

    public function render()
    {
        return view('velm-ui::form.page');
    }
}
