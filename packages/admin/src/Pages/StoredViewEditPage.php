<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Concerns\InteractsWithStoredViewEmbedForm;
use Velm\Admin\Concerns\StoredViewDetailRedirect;
use Velm\Environment;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Views\ViewRegistry;

final class StoredViewEditPage extends ArchEditPage
{
    use InteractsWithStoredViewEmbedForm;
    use StoredViewDetailRedirect;

    protected static ?string $slug = 'views/{module}/{viewName}/{record}/edit';

    public string $module = '';

    public string $viewName = '';

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

    protected function listPageUrl(): string
    {
        return StoredViewRoutes::listPageUrl(
            $this->module,
            StoredViewRoutes::listViewFromFormView($this->viewName),
        );
    }
}
