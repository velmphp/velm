<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

final readonly class FormNotebookPage
{
    /**
     * @param  list<FormCell>  $cells
     */
    public function __construct(
        public string $name,
        public string $title,
        public array $cells,
        public int $cols = 2,
    ) {}
}
