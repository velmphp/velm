<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Framework\VelmManager;
use Velm\Admin\Support\VelmNotify;
use Velm\Modules\AppsCatalog;

final class AppsDetailPage extends VelmShellPage
{
    protected static ?string $slug = 'apps/{name}';

    public string $name = '';

    public function mount(string $name): void
    {
        $this->name = $name;

        if ($this->moduleEntry() === null) {
            abort(404);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return (string) ($this->moduleEntry()['display_name'] ?? $this->name);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function moduleEntry(): ?array
    {
        return (new AppsCatalog)->entry($this->addonPaths(), $this->name);
    }

    public function installModule(): void
    {
        $page = new AppsPage;
        $page->installModule($this->name);
        $this->redirect(AppsDetailPage::getUrl(['name' => $this->name]));
    }

    public function syncModule(): void
    {
        $page = new AppsPage;
        $page->syncModule($this->name);
        $this->redirect(AppsDetailPage::getUrl(['name' => $this->name]));
    }

    public function upgradeModule(): void
    {
        $page = new AppsPage;
        $page->upgradeModule($this->name);
        $this->redirect(AppsDetailPage::getUrl(['name' => $this->name]));
    }

    public function render()
    {
        return view('velm-ui::pages.apps-detail');
    }

    /**
     * @return list<string>
     */
    private function addonPaths(): array
    {
        /** @var list<string> $roots */
        $roots = config('velm.addon_paths', []);

        return $roots;
    }
}
