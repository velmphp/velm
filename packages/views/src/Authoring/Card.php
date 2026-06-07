<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class Card implements ViewDeclaration
{
    private ?string $title = null;

    private ?string $subtitle = null;

    /** @var list<mixed> */
    private array $fields = [];

    /** @var list<mixed> */
    private array $badges = [];

    public static function make(): self
    {
        return new self;
    }

    public function title(string $field): self
    {
        $this->title = $field;

        return $this;
    }

    public function subtitle(string $field): self
    {
        $this->subtitle = $field;

        return $this;
    }

    /**
     * @param  list<mixed>  $fields
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param  list<mixed>  $badges
     */
    public function badges(array $badges): self
    {
        $this->badges = $badges;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $card = [];

        if ($this->title !== null) {
            $card['title'] = $this->title;
        }

        if ($this->subtitle !== null) {
            $card['subtitle'] = $this->subtitle;
        }

        if ($this->fields !== []) {
            $card['fields'] = self::normalizeFieldRefs($this->fields);
        }

        if ($this->badges !== []) {
            $card['badges'] = self::normalizeFieldRefs($this->badges);
        }

        return $card;
    }

    /**
     * @param  list<mixed>  $fields
     * @return list<array<string, mixed>>
     */
    private static function normalizeFieldRefs(array $fields): array
    {
        return array_map(
            static fn (mixed $field): array => $field instanceof ViewDeclaration
                ? $field->toArray()
                : (is_array($field) ? $field : ['name' => (string) $field]),
            $fields,
        );
    }
}
