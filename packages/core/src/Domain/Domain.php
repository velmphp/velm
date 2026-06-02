<?php

declare(strict_types=1);

namespace Velm\Domain;

/**
 * Simple domain leaf: [field, operator, value].
 */
final readonly class Domain
{
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value,
    ) {}

    /**
     * @param  list<mixed>  $domain
     */
    public static function fromArray(array $domain): self
    {
        if (count($domain) !== 3) {
            throw new \InvalidArgumentException('Domain must be a three-element list.');
        }

        return new self((string) $domain[0], (string) $domain[1], $domain[2]);
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $domains
     * @return list<self>
     */
    public static function parseList(array $domains): array
    {
        if ($domains === []) {
            return [];
        }

        if (! is_array($domains[0])) {
            return [self::fromArray($domains)];
        }

        return array_map(self::fromArray(...), $domains);
    }
}
