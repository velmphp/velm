<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Admin\Support\StoredViewRoutes;

/**
 * After save/create, redirect to the matching detail view when available.
 */
trait StoredViewDetailRedirect
{
    protected function detailPageUrl(?int $recordId): ?string
    {
        if ($recordId === null || $recordId <= 0) {
            return null;
        }

        $module = $this->storedViewModule();

        if ($module === null) {
            return null;
        }

        $viewName = $this->storedViewName();

        if ($viewName === null) {
            return null;
        }

        $detailView = StoredViewRoutes::recordViewFromFormView($module, $viewName);

        return StoredViewRoutes::recordPageUrl($module, $detailView, $recordId);
    }

    protected function storedViewModule(): ?string
    {
        if (property_exists($this, 'module') && is_string($this->module) && $this->module !== '') {
            return $this->module;
        }

        if (method_exists($this, 'velmViewModule')) {
            $module = $this->velmViewModule();

            return is_string($module) && $module !== '' ? $module : null;
        }

        return null;
    }

    protected function storedViewName(): ?string
    {
        if (property_exists($this, 'viewName') && is_string($this->viewName) && $this->viewName !== '') {
            return $this->viewName;
        }

        if (method_exists($this, 'velmViewName')) {
            $view = $this->velmViewName();

            return is_string($view) && $view !== '' ? $view : null;
        }

        return null;
    }
}
