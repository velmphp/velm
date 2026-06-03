<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('velm-ui::layouts.shell')]
abstract class VelmShellPage extends Component
{
    protected static ?string $slug = null;

    public static function routePath(): string
    {
        if (static::$slug !== null) {
            return static::$slug;
        }

        return str(class_basename(static::class))
            ->beforeLast('Page')
            ->kebab()
            ->value();
    }

    public static function routeName(): string
    {
        return 'velm.pages.'.class_basename(static::class);
    }

    /**
     * @param  array<string, scalar|null>  $parameters
     */
    public static function getUrl(array $parameters = [], ?string $panel = null): string
    {
        unset($panel);

        return route(static::routeName(), $parameters);
    }

    public function getTitle(): string|Htmlable
    {
        return class_basename(static::class);
    }
}
