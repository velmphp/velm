<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Pages\ArchCreatePage;
use Velm\Admin\Pages\ArchEditPage;
use Velm\Admin\Pages\ArchListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Support\MenuLinkResolver;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Admin\Support\VelmBreadcrumbTier;
use Velm\Admin\Support\VelmPanel;
use Velm\Environment;
use Velm\Views\Menu\MenuTreeBuilder;
use Velm\Views\ViewRegistry;

trait InteractsWithVelmBreadcrumbTrail
{
    /**
     * Home → …fixed tiers… → current page. Not derived from navigation history.
     *
     * @return list<array{label: string, url: string|null}>
     */
    public function velmBreadcrumbTrailJson(): string
    {
        return json_encode(
            $this->velmBreadcrumbTrail(),
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE,
        );
    }

    public function velmNavLabel(): string
    {
        return $this->velmBreadcrumbPageLabel();
    }

    /**
     * @return list<array{label: string, url: string|null}>
     */
    public function velmBreadcrumbTrail(): array
    {
        $trail = [$this->velmBreadcrumbHomeCrumb()];

        return match ($this->velmBreadcrumbTier()) {
            VelmBreadcrumbTier::List => [
                ...$trail,
                ['label' => $this->velmBreadcrumbListLabel(), 'url' => null],
            ],
            VelmBreadcrumbTier::Detail => [
                ...$trail,
                ['label' => $this->velmBreadcrumbListLabel(), 'url' => $this->velmBreadcrumbListUrl()],
                ['label' => $this->velmBreadcrumbDetailLabel(), 'url' => null],
            ],
            VelmBreadcrumbTier::Create => [
                ...$trail,
                ['label' => $this->velmBreadcrumbListLabel(), 'url' => $this->velmBreadcrumbListUrl()],
                ['label' => $this->velmBreadcrumbPageLabel(), 'url' => null],
            ],
            VelmBreadcrumbTier::Edit => $this->velmBreadcrumbEditTrail($trail),
            VelmBreadcrumbTier::Special => [
                ...$trail,
                ['label' => $this->velmBreadcrumbPageLabel(), 'url' => null],
            ],
        };
    }

    protected function velmBreadcrumbTier(): VelmBreadcrumbTier
    {
        if ($this instanceof ArchListPage) {
            return VelmBreadcrumbTier::List;
        }

        if ($this instanceof StoredViewRecordPage) {
            return VelmBreadcrumbTier::Detail;
        }

        if ($this instanceof ArchCreatePage) {
            return VelmBreadcrumbTier::Create;
        }

        if ($this instanceof ArchEditPage) {
            return VelmBreadcrumbTier::Edit;
        }

        return VelmBreadcrumbTier::Special;
    }

    /**
     * @param  list<array{label: string, url: string|null}>  $trail
     * @return list<array{label: string, url: string|null}>
     */
    private function velmBreadcrumbEditTrail(array $trail): array
    {
        $trail[] = ['label' => $this->velmBreadcrumbListLabel(), 'url' => $this->velmBreadcrumbListUrl()];

        $detail = $this->velmBreadcrumbDetailLinkCrumb();

        if ($detail !== null) {
            $trail[] = $detail;
        }

        $trail[] = ['label' => $this->velmBreadcrumbEditLabel(), 'url' => null];

        return $trail;
    }

    protected function velmBreadcrumbEditLabel(): string
    {
        return (string) __('Edit Record');
    }

    /**
     * @return array{label: string, url: string}
     */
    protected function velmBreadcrumbHomeCrumb(): array
    {
        $menu = view()->shared('velmMenu', []);
        $layout = $menu['menu_layout'] ?? 'apps';

        if ($layout === 'apps_catalog') {
            return [
                'label' => (string) __('Home'),
                'url' => VelmPanel::homeUrl(),
            ];
        }

        $root = $menu['menu_active_root'] ?? null;

        if (is_array($root)) {
            $env = app()->bound(\Velm\Environment::class) ? app(\Velm\Environment::class) : null;
            $href = MenuTreeBuilder::entryHref($root, $env);
            $url = MenuLinkResolver::url($href);

            if (is_string($url) && $url !== '') {
                return [
                    'label' => (string) __('Home'),
                    'url' => $url,
                ];
            }
        }

        return [
            'label' => (string) __('Home'),
            'url' => VelmPanel::homeUrl(),
        ];
    }

    protected function velmBreadcrumbPageLabel(): string
    {
        $raw = $this->getTitle();

        return trim(strip_tags($raw instanceof Htmlable ? $raw->toHtml() : (string) $raw));
    }

    protected function velmBreadcrumbDetailLabel(): string
    {
        if ($this->velmBreadcrumbTier() === VelmBreadcrumbTier::Detail) {
            return (string) __('View Record');
        }

        return $this->velmBreadcrumbPageLabel();
    }

    protected function velmBreadcrumbListLabel(): string
    {
        if (property_exists($this, 'module') && property_exists($this, 'viewName')) {
            $module = (string) $this->module;
            $viewName = (string) $this->viewName;

            if ($module !== '' && $viewName !== '') {
                $listView = str_ends_with($viewName, '.detail')
                    ? StoredViewRoutes::listViewFromRecordView($viewName)
                    : StoredViewRoutes::listViewFromFormView($viewName);

                $arch = app(ViewRegistry::class)->arch(app(Environment::class), $module, $listView);
                $title = $arch['title'] ?? null;

                if (is_string($title) && $title !== '') {
                    return $title;
                }
            }
        }

        if (method_exists($this, 'arch')) {
            $title = $this->arch()['title'] ?? null;

            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        return $this->velmBreadcrumbPageLabel();
    }

    protected function velmBreadcrumbListUrl(): string
    {
        if (method_exists($this, 'listPageUrl')) {
            return $this->listPageUrl();
        }

        return url()->current();
    }

    /**
     * Detail step on edit pages (linked when a detail view exists).
     *
     * @return array{label: string, url: string}|null
     */
    protected function velmBreadcrumbDetailLinkCrumb(): ?array
    {
        if (! property_exists($this, 'record') || (int) $this->record <= 0) {
            return null;
        }

        if (! method_exists($this, 'detailPageUrl')) {
            return null;
        }

        $detailUrl = $this->detailPageUrl((int) $this->record);

        if ($detailUrl === null) {
            return null;
        }

        return [
            'label' => (string) __('View Record'),
            'url' => $detailUrl,
        ];
    }
}
