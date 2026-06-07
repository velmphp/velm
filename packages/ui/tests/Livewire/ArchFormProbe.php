<?php

declare(strict_types=1);

namespace Velm\Ui\Tests\Livewire;

use Livewire\Component;
use Velm\Ui\Concerns\InteractsWithVelmArchForm;
use Velm\Ui\Forms\FormMode;

class ArchFormProbe extends Component
{
    use InteractsWithVelmArchForm {
        redirectAfterVelmFormSubmit as protected traitRedirectAfterVelmFormSubmit;
    }

    public int $record = 0;

    public string $module = 'partners';

    public string $viewName = 'partner.form';

    public FormMode $mode = FormMode::Edit;

    public bool $skipRedirect = true;

    public bool $embedded = false;

    public bool $embedUrlAlreadyTagged = false;

    /**
     * @return array<string, mixed>
     */
    protected function arch(): array
    {
        return [
            'title' => 'Partner',
            'model' => 'res.partner',
            'sections' => [[
                'name' => 'main',
                'fields' => [
                    ['name' => 'name'],
                    ['name' => 'active'],
                ],
            ]],
        ];
    }

    protected function listPageUrl(): string
    {
        return '/velm/views/partners/partner.list';
    }

    protected function velmFormMode(): FormMode
    {
        return $this->mode;
    }

    protected function velmFormViewModule(): ?string
    {
        return $this->module;
    }

    protected function velmFormViewName(): ?string
    {
        return $this->viewName;
    }

    protected function velmFormEmbedded(): bool
    {
        return $this->embedded || request()->boolean('embed');
    }

    protected function velmFormEmbedRecordUrl(int $recordId): ?string
    {
        $url = '/velm/views/'.$this->module.'/'.$this->viewName.'/'.$recordId;

        if ($this->embedUrlAlreadyTagged) {
            return $url.'?embed=1';
        }

        return $url;
    }

    protected function detailPageUrl(?int $recordId): ?string
    {
        return $recordId !== null
            ? '/velm/views/'.$this->module.'/partner.detail/'.$recordId
            : null;
    }

    protected function redirectAfterVelmFormSubmit(?int $recordId = null): void
    {
        if ($this->skipRedirect) {
            return;
        }

        $this->traitRedirectAfterVelmFormSubmit($recordId);
    }

    public function invokeRedirectAfterSubmit(?int $recordId = null): void
    {
        $this->traitRedirectAfterVelmFormSubmit($recordId);
    }

    public function mount(int $record = 0): void
    {
        $this->record = $record;

        if ($record > 0 && $this->mode === FormMode::Edit) {
            $this->fillVelmFormFromRecord($record);
        }

        if ($this->mode === FormMode::New) {
            $this->resetVelmForm();
        }
    }

    public function render(): string
    {
        return '<div>probe</div>';
    }
}
