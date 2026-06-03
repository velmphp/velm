<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

final readonly class FormSectionView
{
    /**
     * @param  list<FormCell>  $cells
     * @param  list<FormNotebookPage>  $pages
     */
    public function __construct(
        public FormLayoutKind $kind,
        public string $name,
        public string $title,
        public int $cols = 2,
        public array $cells = [],
        public array $pages = [],
        public ?string $storageKey = null,
    ) {}

    /**
     * @param  list<FormCell>  $cells
     */
    public static function section(string $name, string $title, array $cells, int $cols = 2): self
    {
        return new self(FormLayoutKind::Section, $name, $title, $cols, $cells);
    }

    /**
     * @param  list<FormNotebookPage>  $pages
     */
    public static function notebook(string $name, string $title, array $pages, string $storageKey, int $cols = 2): self
    {
        return new self(FormLayoutKind::Notebook, $name, $title, $cols, pages: $pages, storageKey: $storageKey);
    }
}
