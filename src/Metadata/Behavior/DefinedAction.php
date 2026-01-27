<?php

namespace Velm\Core\Metadata\Behavior;

use Velm\Core\Metadata\Fields\VelmField;

final class DefinedAction
{
    public string $name;

    /**
     * @var array<string, VelmField> The input parameters for the action
     */
    public array $parameters = [];

    public array $returns = [];

    public array $guards = [];

    public array $sideEffects = [];

    public array $contexts = [];

    public array $tags = [];

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function parameter(velmField $param): self
    {
        $this->parameters[$param->getName()] = $param;

        return $this;
    }

    public function returns(string $name, string $type, string $description = ''): self
    {
        $this->returns[$name] = compact('type', 'description');

        return $this;
    }

    public function guard(string $name): self
    {
        $this->guards[] = $name;

        return $this;
    }

    public function sideEffect(string $name): self
    {
        $this->sideEffects[] = $name;

        return $this;
    }

    public function context(string $name): self
    {
        $this->contexts[] = $name;

        return $this;
    }

    public function tag(string $name): self
    {
        $this->tags[] = $name;

        return $this;
    }
}
