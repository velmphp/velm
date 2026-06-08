<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Concerns\InteractsWithStoredViewEmbedForm;
use Velm\Admin\Concerns\InteractsWithVelmMailThread;
use Velm\Admin\Concerns\InteractsWithVelmViewActions;
use Velm\Admin\Concerns\InteractsWithVelmWorkflow;
use Velm\Environment;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Ui\Concerns\InteractsWithVelmArchForm;
use Velm\Ui\Forms\FormMode;
use Velm\Views\ViewRegistry;

final class StoredViewRecordPage extends VelmShellPage
{
    use InteractsWithStoredViewEmbedForm;
    use InteractsWithVelmArchForm;
    use InteractsWithVelmMailThread;
    use InteractsWithVelmViewActions;
    use InteractsWithVelmWorkflow;

    protected static ?string $slug = 'views/{module}/{viewName}/{record}';

    public string $module = '';

    public string $viewName = '';

    public int|string $record = 0;

    /**
     * @return array<string, mixed>
     */
    protected function arch(): array
    {
        return app(ViewRegistry::class)->arch(
            app(Environment::class),
            $this->module,
            $this->viewName,
        );
    }

    protected function velmFormMode(): FormMode
    {
        return FormMode::Display;
    }

    public function mount(int|string $record): void
    {
        $this->record = (int) $record;
        $this->fillVelmFormFromRecord($this->record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'View '.$this->velmRecordDisplayName();
    }

    protected function listPageUrl(): string
    {
        return StoredViewListPage::getUrl([
            'module' => $this->module,
            'viewName' => StoredViewRoutes::listViewFromRecordView($this->viewName),
        ]);
    }

    public function velmEditPageUrl(): string
    {
        $listArch = app(ViewRegistry::class)->arch(
            app(Environment::class),
            $this->module,
            StoredViewRoutes::listViewFromRecordView($this->viewName),
        );

        $formView = $listArch['form_view'] ?? $listArch['edit_view'] ?? null;

        if (! is_string($formView) || $formView === '') {
            throw new \LogicException("No form_view on list arch for {$this->module}.{$this->viewName}.");
        }

        return StoredViewEditPage::getUrl([
            'module' => $this->module,
            'viewName' => $formView,
            'record' => $this->record,
        ]);
    }

    public function render()
    {
        return view('velm-ui::form.page');
    }
}
