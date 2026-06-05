<?php

declare(strict_types=1);

namespace Velm\Modules;

/**
 * Fluent builder for {@see __velm__.php} module manifests.
 *
 * @example
 * return Manifest::make('partners')
 *     ->version(0, 1, 0)
 *     ->depends('base')
 *     ->models(Partner::class, Country::class)
 *     ->summary('Contacts and addresses')
 *     ->category('Sales');
 */
final class Manifest
{
    /** @var list<int> */
    private array $version = [];

    /** @var list<string> */
    private array $depends = [];

    /** @var list<string> */
    private array $data = [];

    /** @var list<class-string> */
    private array $models = [];

    /** @var list<class-string> */
    private array $seeders = [];

    private string $summary = '';

    private string $description = '';

    private string $category = '';

    private string $author = '';

    private string $icon = '';

    private ?string $syncHook = null;

    private ?string $installHook = null;

    private function __construct(
        private readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Module name must not be empty.');
        }

        return new self($name);
    }

    /**
     * @param  int|list<int>  $first  Version segment or full `[major, minor, patch]` array.
     * @param  int  ...$rest
     */
    public function version(int|array $first, int ...$rest): self
    {
        if (is_array($first)) {
            if ($rest !== []) {
                throw new \InvalidArgumentException('Pass either version(0, 1, 0) or version([0, 1, 0]), not both.');
            }

            if ($first === []) {
                throw new \InvalidArgumentException('version() requires at least one segment.');
            }

            $this->version = array_map(intval(...), $first);

            return $this;
        }

        $this->version = [$first, ...$rest];

        if ($this->version === []) {
            throw new \InvalidArgumentException('version() requires at least one segment.');
        }

        return $this;
    }

    public function depends(string ...$modules): self
    {
        $this->depends = array_values($modules);

        return $this;
    }

    public function data(string ...$paths): self
    {
        $this->data = array_values($paths);

        return $this;
    }

    /**
     * @param  class-string  ...$modelClasses
     */
    public function models(string ...$modelClasses): self
    {
        $this->models = array_values($modelClasses);

        return $this;
    }

    /**
     * @param  class-string  ...$seederClasses
     */
    public function seeders(string ...$seederClasses): self
    {
        $this->seeders = array_values($seederClasses);

        return $this;
    }

    public function summary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function author(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  class-string  $class
     */
    public function syncHook(string $class, string $method = 'sync'): self
    {
        $this->syncHook = $class.'::'.$method;

        return $this;
    }

    public function syncHookReference(): ?string
    {
        return $this->syncHook;
    }

    /**
     * @param  class-string  $class
     */
    public function installHook(string $class, string $method = 'install'): self
    {
        $this->installHook = $class.'::'.$method;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->version === []) {
            throw new \LogicException("Manifest for {$this->name} is missing version().");
        }

        $manifest = [
            'NAME' => $this->name,
            'VERSION' => $this->version,
            'DEPENDS' => $this->depends,
            'DATA' => $this->data,
        ];

        if ($this->models !== []) {
            $manifest['MODELS'] = $this->models;
        }

        if ($this->seeders !== []) {
            $manifest['SEEDERS'] = $this->seeders;
        }

        if ($this->summary !== '') {
            $manifest['SUMMARY'] = $this->summary;
        }

        if ($this->description !== '') {
            $manifest['DESCRIPTION'] = $this->description;
        }

        if ($this->category !== '') {
            $manifest['CATEGORY'] = $this->category;
        }

        if ($this->author !== '') {
            $manifest['AUTHOR'] = $this->author;
        }

        if ($this->icon !== '') {
            $manifest['ICON'] = $this->icon;
        }

        if ($this->syncHook !== null) {
            $manifest['SYNC_HOOK'] = $this->syncHook;
        }

        if ($this->installHook !== null) {
            $manifest['INSTALL_HOOK'] = $this->installHook;
        }

        return $manifest;
    }
}
