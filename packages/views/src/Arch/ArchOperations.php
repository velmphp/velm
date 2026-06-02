<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

/**
 * Apply VIEW_INHERITS operations to normalized arch (PyVelm parity).
 */
final class ArchOperations
{
    /**
     * @param  array<string, mixed>  $arch
     * @param  list<array<string, mixed>>  $operations
     * @return array<string, mixed>
     */
    public static function apply(array &$arch, array $operations): array
    {
        foreach ($operations as $op) {
            $kind = (string) ($op['op'] ?? '');
            /** @var list<mixed> $target */
            $target = array_values($op['target'] ?? []);

            if ($target !== [] && $target[0] === '**') {
                if (count($target) < 2) {
                    throw new \InvalidArgumentException(
                        "'**' must be followed by at least one selector (`name` string or predicate dict)",
                    );
                }

                $entrySeg = $target[1];

                try {
                    $archRoot = &self::findDescendant($arch, $entrySeg);
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException(
                        '`**` lookup found no descendant matching '.json_encode($entrySeg),
                        0,
                        $e,
                    );
                }

                $target = array_slice($target, 1);
            } else {
                $archRoot = &$arch;
            }

            if ($kind === 'update') {
                $node = &$archRoot;

                foreach ($target as $seg) {
                    $node = &self::stepIntoRef($node, $seg);
                }

                $value = $op['value'] ?? null;

                if (! is_array($value)) {
                    throw new \InvalidArgumentException(
                        "'update' value must be a dict, got ".gettype($value),
                    );
                }

                /** @var array<string, mixed> $value */
                foreach ($value as $key => $val) {
                    $node[$key] = $val;
                }

                continue;
            }

            if ($target === []) {
                throw new \InvalidArgumentException('Operation has empty target');
            }

            $parent = &$archRoot;

            foreach (array_slice($target, 0, -1) as $seg) {
                $parent = &self::stepIntoRef($parent, $seg);
            }

            $last = $target[count($target) - 1];

            match ($kind) {
                'remove' => self::removeAt($parent, $last),
                'set', 'replace' => self::setAt($parent, $last, $op['value'] ?? null),
                'before' => self::insertAt($parent, $last, $op['value'] ?? null, before: true),
                'after' => self::insertAt($parent, $last, $op['value'] ?? null, before: false),
                default => throw new \InvalidArgumentException("Unknown view-arch op: {$kind}"),
            };
        }

        return $arch;
    }

    /**
     * @param  array<mixed>  $parent
     */
    private static function removeAt(array &$parent, mixed $last): void
    {
        $position = self::resolvePosition($parent, $last);

        if (array_is_list($parent) && is_int($position)) {
            array_splice($parent, $position, 1);

            return;
        }

        if (! array_is_list($parent) && is_string($position)) {
            unset($parent[$position]);
        }
    }

    /**
     * @param  array<mixed>  $parent
     */
    private static function setAt(array &$parent, mixed $last, mixed $value): void
    {
        if (! array_is_list($parent) && is_string($last)) {
            $parent[$last] = $value;

            return;
        }

        $position = self::resolvePosition($parent, $last);

        if (array_is_list($parent) && is_int($position)) {
            $parent[$position] = $value;
        }
    }

    /**
     * @param  array<mixed>  $parent
     */
    private static function insertAt(array &$parent, mixed $last, mixed $value, bool $before): void
    {
        if (! array_is_list($parent)) {
            throw new \InvalidArgumentException(
                "'".($before ? 'before' : 'after')."' requires a list parent, got array",
            );
        }

        $position = self::resolvePosition($parent, $last);
        $index = $before ? $position : $position + 1;
        array_splice($parent, $index, 0, [$value]);
    }

    /**
     * @param  array<string, mixed>  $root
     * @return array<mixed>
     */
    private static function &findDescendant(array &$root, mixed $seg): array
    {
        try {
            self::stepIntoRef($root, $seg);

            return $root;
        } catch (\RuntimeException|\InvalidArgumentException|\OutOfRangeException) {
        }

        foreach ($root as $key => $_) {
            if (! is_array($root[$key])) {
                continue;
            }

            try {
                return self::findDescendant($root[$key], $seg);
            } catch (\RuntimeException) {
            }
        }

        throw new \RuntimeException('no descendant matched '.json_encode($seg));
    }

    public static function stepInto(array $node, mixed $seg): mixed
    {
        return self::stepIntoRef($node, $seg);
    }

    /**
     * @param  array<mixed>  $node
     * @return array<mixed>
     */
    private static function &stepIntoRef(array &$node, mixed $seg): array
    {
        if (array_is_list($node)) {
            if (is_int($seg)) {
                if (! array_key_exists($seg, $node)) {
                    throw new \OutOfRangeException("index {$seg} out of range");
                }

                return $node[$seg];
            }

            if (is_string($seg)) {
                foreach ($node as $i => $item) {
                    if (is_array($item) && ($item['name'] ?? null) === $seg) {
                        return $node[$i];
                    }
                }

                throw new \RuntimeException("no list entry named {$seg}");
            }

            if (is_array($seg)) {
                foreach ($node as $i => $item) {
                    if (self::matchesPredicate($item, $seg)) {
                        return $node[$i];
                    }
                }

                throw new \RuntimeException('no list entry matching predicate '.json_encode($seg));
            }

            throw new \InvalidArgumentException("can't step into list with ".gettype($seg));
        }

        if (is_string($seg)) {
            if (! array_key_exists($seg, $node)) {
                throw new \RuntimeException("no key {$seg}");
            }

            return $node[$seg];
        }

        throw new \InvalidArgumentException("can't step into array with ".gettype($seg));
    }

    /**
     * @param  array<mixed>  $parent
     */
    public static function resolvePosition(array $parent, mixed $seg): int|string
    {
        if (array_is_list($parent)) {
            if (is_int($seg)) {
                if ($seg < 0 || $seg >= count($parent)) {
                    throw new \RuntimeException("index {$seg} out of range");
                }

                return $seg;
            }

            if (is_string($seg)) {
                foreach ($parent as $i => $item) {
                    if (is_array($item) && ($item['name'] ?? null) === $seg) {
                        return $i;
                    }
                }

                throw new \RuntimeException("no list entry named {$seg}");
            }

            if (is_array($seg)) {
                foreach ($parent as $i => $item) {
                    if (self::matchesPredicate($item, $seg)) {
                        return $i;
                    }
                }

                throw new \RuntimeException('no list entry matching predicate '.json_encode($seg));
            }
        }

        if (! array_is_list($parent) && is_string($seg)) {
            if (! array_key_exists($seg, $parent)) {
                throw new \RuntimeException("no key {$seg}");
            }

            return $seg;
        }

        throw new \InvalidArgumentException("can't address array with ".gettype($seg));
    }

    /**
     * @param  array<string, mixed>  $predicate
     */
    public static function matchesPredicate(mixed $item, array $predicate): bool
    {
        if (! is_array($item)) {
            return false;
        }

        foreach ($predicate as $key => $value) {
            if (($item[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }
}
