<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

/**
 * Fluent path builder for view inherit operation targets.
 *
 * @example ViewTarget::section('identity')->field('name')->segments()
 */
final class ViewTarget
{
    /** @var list<mixed> */
    private array $segments;

    /**
     * @param  list<mixed>  $segments
     */
    private function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    public static function path(string $dotPath): self
    {
        if ($dotPath === '') {
            throw new \InvalidArgumentException('View target path must not be empty.');
        }

        return new self(explode('.', $dotPath));
    }

    public static function cols(): self
    {
        return new self(['cols']);
    }

    public static function section(string $name): self
    {
        return new self(['sections', $name]);
    }

    public static function column(string $name): self
    {
        return new self(['fields', $name]);
    }

    public function fields(): self
    {
        return new self([...$this->segments, 'fields']);
    }

    public function field(string $name): self
    {
        return new self([...$this->segments, 'fields', $name]);
    }

    public function append(string ...$segments): self
    {
        return new self([...$this->segments, ...$segments]);
    }

    /**
     * @return list<mixed>
     */
    public function segments(): array
    {
        return $this->segments;
    }
}
