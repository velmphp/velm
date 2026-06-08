<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Admin\Concerns\InteractsWithVelmListPresentation;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Environment;
use Velm\Views\ViewRegistry;

trait ResolvesStoredView
{
    abstract protected function velmViewModule(): string;

    abstract protected function velmViewName(): string;

    /**
     * @return array<string, mixed>
     */
    protected function arch(): array
    {
        return app(ViewRegistry::class)->arch(
            app(Environment::class),
            $this->velmViewModule(),
            $this->velmViewName(),
        );
    }

    protected function listFormViewName(): ?string
    {
        if ($this->listIsReadonly()) {
            return null;
        }

        $arch = $this->arch();

        $view = $arch['form_view'] ?? null;

        return is_string($view) && $view !== '' ? $view : null;
    }

    protected function listDetailViewName(): ?string
    {
        $arch = $this->arch();

        $view = $arch['detail_view'] ?? $arch['record_view'] ?? null;

        if (is_string($view) && $view !== '') {
            return $view;
        }

        $formView = $arch['form_view'] ?? null;

        if (is_string($formView) && str_ends_with($formView, '.form')) {
            return substr($formView, 0, -strlen('.form')).'.detail';
        }

        return null;
    }

    protected function listEditViewName(): ?string
    {
        if ($this->listIsReadonly()) {
            return null;
        }

        $arch = $this->arch();

        $view = $arch['edit_view'] ?? $arch['form_view'] ?? null;

        return is_string($view) && $view !== '' ? $view : null;
    }

    protected function createPageUrl(): ?string
    {
        if ($this->listIsReadonly()) {
            return null;
        }

        $formView = $this->listFormViewName();

        if ($formView === null) {
            return null;
        }

        return StoredViewRoutes::createPageUrl($this->velmViewModule(), $formView);
    }

    protected function listIsReadonly(): bool
    {
        return filter_var($this->arch()['readonly'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function openRecordUrl(int $recordId): ?string
    {
        $detailView = $this->listDetailViewName();

        if ($detailView === null) {
            return null;
        }

        return StoredViewRecordPage::getUrl([
            'module' => $this->velmViewModule(),
            'viewName' => $detailView,
            'record' => $recordId,
        ], panel: 'velm');
    }

    protected function editRecordUrl(int $recordId): ?string
    {
        $editView = $this->listEditViewName();

        if ($editView === null) {
            return null;
        }

        return StoredViewEditPage::getUrl([
            'module' => $this->velmViewModule(),
            'viewName' => $editView,
            'record' => $recordId,
        ], panel: 'velm');
    }

    protected function supportsRecordOpen(): bool
    {
        return $this->listDetailViewName() !== null;
    }

    protected function supportsRecordEdit(): bool
    {
        if ($this->listIsReadonly()) {
            return false;
        }

        return $this->listEditViewName() !== null;
    }

    protected function listHasOpenTarget(): bool
    {
        return $this->listDetailViewName() !== null;
    }

    protected function listHasEditTarget(): bool
    {
        return $this->supportsRecordEdit();
    }

    /**
     * @return list<array{type: string, label: string, url: string, active: bool}>
     */
    public function analyticsViewSwitcher(): array
    {
        $arch = $this->arch();

        return app(AnalyticsViewSwitcher::class)->items(
            app(Environment::class),
            $this->velmViewModule(),
            $this->velmViewName(),
            (string) ($arch['model'] ?? ''),
        );
    }
}
