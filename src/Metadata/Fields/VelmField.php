<?php

namespace Velm\Core\Metadata\Fields;

use Velm\Core\Metadata\Types\FieldType;

final class VelmField
{
    private string $name;

    private FieldType $type;

    private bool $nullable = false;

    private bool $required = false;

    private bool $immutable = false;

    private mixed $default = null;

    private ?string $label = null;

    private ?string $description = null;

    private ?int $position = null;

    private array $constraints = [];

    private array $semantics = [];

    private array $capabilities = [
        'read' => true,
        'create' => true,
        'update' => true,
    ];

    private function __construct(
        string $name,
        FieldType $type,
    ) {
        $this->name = $name;
        $this->type = $type;
    }

    /* ---------- Static Constructors ---------- */

    public static function string(string $name): self
    {
        return new self($name, FieldType::String);
    }

    public static function decimal(string $name): self
    {
        return new self($name, FieldType::Decimal);
    }

    public static function make(string $name, FieldType $type): self
    {
        return new self($name, $type);
    }

    // others omitted for brevity

    /* ---------- Modifiers ---------- */

    public function nullable(bool $nullable = false): self
    {
        $this->nullable = $nullable;
        $this->required = ! $nullable;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;
        $this->nullable = ! $required;

        return $this;
    }

    public function immutable(bool $immutable = true): self
    {
        $this->immutable = $immutable;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function describe(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function constraint(string $key, mixed $value): self
    {
        $this->constraints[$key] = $value;

        return $this;
    }

    public function semantic(string $key, mixed $value = true): self
    {
        $this->semantics[$key] = $value;

        return $this;
    }

    public function capability(string $key, bool $value): self
    {
        $this->capabilities[$key] = $value;

        return $this;
    }

    public function position(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @param  array<string,mixed>  $capabilities
     * @return $this
     */
    public function capabilities(array $capabilities): self
    {
        $this->capabilities = $capabilities;

        return $this;
    }

    /**
     * @param  array<string,mixed>  $constraints
     * @return $this
     */
    public function constraints(array $constraints): self
    {
        $this->constraints = $constraints;

        return $this;
    }

    /**
     * @param  array<string,mixed>  $semantics
     * @return $this
     */
    public function semantics(array $semantics): self
    {
        $this->semantics = $semantics;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isImmutable(): bool
    {
        return $this->immutable;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getSemantics(): array
    {
        return $this->semantics;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function toArray(): array
    {
        $props = get_object_vars($this);

        return array_map(function ($value) {
            return $value;
        }, $props);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): FieldType
    {
        return $this->type;
    }
}
