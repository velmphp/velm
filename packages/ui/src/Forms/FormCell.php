<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

/**
 * One field cell in the form grid (PyVelm `cell` dict).
 */
final readonly class FormCell
{
    /**
     * @param  array<string, mixed>  $widgetProps  Passed to the Blade widget partial
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $widget,
        public array $widgetProps,
        public bool $required = false,
        public ?string $error = null,
        public int $colspan = 1,
        public bool $wide = false,
    ) {}
}
