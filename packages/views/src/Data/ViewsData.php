<?php

declare(strict_types=1);

namespace Velm\Views\Data;

use Velm\Views\Authoring\Contracts\MenuDeclaration;
use Velm\Views\Authoring\Contracts\ViewDeclaration;

/**
 * Fluent builder for module DATA view files (like {@see Manifest} for __velm__.php).
 *
 * @example
 * return ViewsData::make()
 *     ->views(
 *         ListView::make('partner.list')->model('res.partner')->…,
 *         FormView::make('partner.form')->…,
 *     )
 *     ->inherits(
 *         InheritView::make('partner.list.ext', 'partners.partner.list', […]),
 *     );
 */
final class ViewsData
{
    /** @var list<ViewDeclaration> */
    private array $views = [];

    /** @var list<ViewDeclaration> */
    private array $inherits = [];

    /** @var list<MenuDeclaration> */
    private array $menus = [];

    public static function make(): self
    {
        return new self;
    }

    public function views(ViewDeclaration ...$views): self
    {
        foreach ($views as $view) {
            $this->views[] = $view;
        }

        return $this;
    }

    public function view(ViewDeclaration $view): self
    {
        $this->views[] = $view;

        return $this;
    }

    public function inherits(ViewDeclaration ...$inherits): self
    {
        foreach ($inherits as $inherit) {
            $this->inherits[] = $inherit;
        }

        return $this;
    }

    public function inherit(ViewDeclaration $inherit): self
    {
        $this->inherits[] = $inherit;

        return $this;
    }

    public function menus(MenuDeclaration ...$menus): self
    {
        foreach ($menus as $menu) {
            $this->menus[] = $menu;
        }

        return $this;
    }

    public function menu(MenuDeclaration $menu): self
    {
        $this->menus[] = $menu;

        return $this;
    }

    /**
     * @return array{
     *     VIEWS?: list<array<string, mixed>>,
     *     VIEW_INHERITS?: list<array<string, mixed>>,
     *     MENUS?: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->views !== []) {
            $data['VIEWS'] = array_map(static fn (ViewDeclaration $view): array => $view->toArray(), $this->views);
        }

        if ($this->inherits !== []) {
            $data['VIEW_INHERITS'] = array_map(static fn (ViewDeclaration $inherit): array => $inherit->toArray(), $this->inherits);
        }

        if ($this->menus !== []) {
            $data['MENUS'] = array_map(static fn (MenuDeclaration $menu): array => $menu->toArray(), $this->menus);
        }

        return $data;
    }
}
