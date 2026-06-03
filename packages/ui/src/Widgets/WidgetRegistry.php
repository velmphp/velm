<?php

declare(strict_types=1);

namespace Velm\Ui\Widgets;

use Velm\Fields\BooleanField;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Fields\One2manyField;
use Velm\Ui\Forms\FormMode;

/**
 * Maps Velm field types (+ arch widget hints) to Blade widget partials.
 */
final class WidgetRegistry
{
    /** @var array<string, string> */
    private array $map = [];

    public function __construct()
    {
        $this->registerEditWidgets();
        $this->registerDisplayWidgets();
    }

    private function registerEditWidgets(): void
    {
        $this->register(BooleanField::class, null, FormMode::Edit, 'velm-ui::widgets.boolean');
        $this->register(BooleanField::class, 'toggle', FormMode::Edit, 'velm-ui::widgets.boolean-toggle');
        $this->register(Many2oneField::class, null, FormMode::Edit, 'velm-ui::widgets.m2o-input');
        $this->register(Many2manyField::class, null, FormMode::Edit, 'velm-ui::widgets.m2m-input');
        $this->register(Many2manyField::class, 'dialog', FormMode::Edit, 'velm-ui::widgets.m2m-input');
        $this->register(One2manyField::class, null, FormMode::Edit, 'velm-ui::widgets.o2m-dialog');
        $this->register(One2manyField::class, 'dialog', FormMode::Edit, 'velm-ui::widgets.o2m-dialog');
        $this->register(One2manyField::class, 'inline', FormMode::Edit, 'velm-ui::widgets.o2m-dialog');
        $this->register(One2manyField::class, 'table', FormMode::Edit, 'velm-ui::widgets.o2m-dialog');
        $this->registerDefault(FormMode::Edit, 'velm-ui::widgets.char-input');
    }

    private function registerDisplayWidgets(): void
    {
        $this->register(BooleanField::class, null, FormMode::Display, 'velm-ui::widgets.display.boolean');
        $this->register(BooleanField::class, 'toggle', FormMode::Display, 'velm-ui::widgets.display.boolean');
        $this->register(Many2oneField::class, null, FormMode::Display, 'velm-ui::widgets.display.m2o');
        $this->register(Many2manyField::class, null, FormMode::Display, 'velm-ui::widgets.display.m2m');
        $this->register(Many2manyField::class, 'dialog', FormMode::Display, 'velm-ui::widgets.display.m2m');
        $this->register(One2manyField::class, null, FormMode::Display, 'velm-ui::widgets.display.o2m');
        $this->register(One2manyField::class, 'dialog', FormMode::Display, 'velm-ui::widgets.display.o2m');
        $this->register(One2manyField::class, 'inline', FormMode::Display, 'velm-ui::widgets.display.o2m');
        $this->registerDefault(FormMode::Display, 'velm-ui::widgets.display.char');
    }

    /**
     * @param  class-string<Field>  $fieldClass
     */
    public function register(
        string $fieldClass,
        ?string $hint,
        FormMode $mode,
        string $bladeView,
    ): void {
        $this->map[$this->key($fieldClass, $hint, $mode)] = $bladeView;
    }

    public function registerDefault(FormMode $mode, string $bladeView): void
    {
        $this->map[$this->key(Field::class, null, $mode)] = $bladeView;
    }

    public function resolve(WidgetContext $ctx): string
    {
        $field = $ctx->velmField();
        $hint = isset($ctx->spec['widget']) && is_string($ctx->spec['widget'])
            ? $ctx->spec['widget']
            : null;
        $mode = $this->widgetLookupMode($ctx->mode);

        if ($field !== null) {
            foreach ($this->walkFieldClasses($field) as $class) {
                $view = $this->map[$this->key($class, $hint, $mode)] ?? null;
                if ($view !== null) {
                    return $view;
                }

                $view = $this->map[$this->key($class, null, $mode)] ?? null;
                if ($view !== null) {
                    return $view;
                }
            }
        }

        return $this->map[$this->key(Field::class, null, $mode)]
            ?? 'velm-ui::widgets.char-input';
    }

    /**
     * Create forms use {@see FormMode::New}; widget bindings reuse edit widgets.
     */
    private function widgetLookupMode(FormMode $mode): FormMode
    {
        return $mode === FormMode::New ? FormMode::Edit : $mode;
    }

    /**
     * @return list<class-string<Field>>
     */
    private function walkFieldClasses(Field $field): array
    {
        $classes = [];
        $class = $field::class;

        while (is_subclass_of($class, Field::class)) {
            $classes[] = $class;
            $class = get_parent_class($class) ?: Field::class;
        }

        $classes[] = Field::class;

        return $classes;
    }

    /**
     * @param  class-string  $fieldClass
     */
    private function key(string $fieldClass, ?string $hint, FormMode $mode): string
    {
        return $fieldClass.'|'.($hint ?? '').'|'.$mode->value;
    }
}
