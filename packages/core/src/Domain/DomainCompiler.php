<?php

declare(strict_types=1);

namespace Velm\Domain;

/**
 * Odoo-style prefix domain compiler: {@see expandOrGroups}, {@see normalizeDomain},
 * then compile to SQL with AND / OR / NOT.
 */
final class DomainCompiler
{
    /** @var list<string> */
    private const POLISH_OPS = ['&', '|', '!'];

    /**
     * Expand legacy {@code ['__or__', 'ilike', [sub_leaves…]]} into {@code |} prefix groups.
     *
     * @param  list<mixed>  $domain
     * @return list<mixed>
     */
    public static function expandOrGroups(array $domain): array
    {
        $out = [];

        foreach ($domain as $item) {
            if (
                is_array($item)
                && count($item) === 3
                && $item[0] === '__or__'
            ) {
                /** @var list<mixed> $subs */
                $subs = is_array($item[2]) ? array_values($item[2]) : [];

                if ($subs === []) {
                    continue;
                }

                if (count($subs) === 1) {
                    $out[] = $subs[0];

                    continue;
                }

                for ($i = 0; $i < count($subs) - 1; $i++) {
                    $out[] = '|';
                }

                foreach ($subs as $sub) {
                    $out[] = $sub;
                }

                continue;
            }

            $out[] = $item;
        }

        return $out;
    }

    /**
     * Make implicit AND explicit (Odoo normalize_domain semantics).
     *
     * @param  list<mixed>  $domain
     * @return list<mixed>
     */
    public static function normalizeDomain(array $domain): array
    {
        if ($domain === []) {
            return [];
        }

        $result = [];
        $expected = 1;
        $opArity = ['!' => 1, '&' => 2, '|' => 2];

        foreach ($domain as $token) {
            if ($expected === 0) {
                array_unshift($result, '&');
                $expected = 1;
            }

            if (self::isLeaf($token)) {
                $result[] = $token;
                $expected--;

                continue;
            }

            if (is_string($token) && array_key_exists($token, $opArity)) {
                $result[] = $token;
                $expected += $opArity[$token] - 1;

                continue;
            }

            throw new \InvalidArgumentException('Invalid domain token '.json_encode($token).'.');
        }

        if ($expected !== 0) {
            throw new \InvalidArgumentException('Invalid domain '.json_encode($domain).'.');
        }

        return $result;
    }

    public static function isLeaf(mixed $token): bool
    {
        if (! is_array($token) || ! array_is_list($token) || count($token) < 3) {
            return false;
        }

        if (! is_string($token[0])) {
            return false;
        }

        return ! in_array($token[0], self::POLISH_OPS, true);
    }

    /**
     * @param  list<mixed>  $domain
     * @param  callable(Domain): string  $leafCompiler
     * @param  list<mixed>  $params
     */
    public function compileWhere(array $domain, callable $leafCompiler, array &$params): string
    {
        $expanded = self::expandOrGroups($domain);

        if ($expanded === []) {
            return '';
        }

        $normalized = self::normalizeDomain($expanded);
        [$tree, $end] = $this->parsePolish($normalized, 0);

        if ($end !== count($normalized)) {
            throw new \InvalidArgumentException('Trailing tokens in domain.');
        }

        return $this->compileTree($tree, $leafCompiler, $params);
    }

    /**
     * @param  list<mixed>  $domain
     * @return array{0: mixed, 1: int}
     */
    private function parsePolish(array $domain, int $pos): array
    {
        if ($pos >= count($domain)) {
            throw new \InvalidArgumentException('Unexpected end of domain.');
        }

        $tok = $domain[$pos];

        if ($tok === '!') {
            [$child, $pos] = $this->parsePolish($domain, $pos + 1);

            return [['!', $child], $pos];
        }

        if ($tok === '&') {
            [$left, $pos] = $this->parsePolish($domain, $pos + 1);
            [$right, $pos] = $this->parsePolish($domain, $pos);

            return [['&', $left, $right], $pos];
        }

        if ($tok === '|') {
            [$left, $pos] = $this->parsePolish($domain, $pos + 1);
            [$right, $pos] = $this->parsePolish($domain, $pos);

            return [['|', $left, $right], $pos];
        }

        if (self::isLeaf($tok)) {
            return [['leaf', $tok], $pos + 1];
        }

        throw new \InvalidArgumentException('Invalid domain token '.json_encode($tok).' at position '.$pos.'.');
    }

    /**
     * @param  callable(Domain): string  $leafCompiler
     * @param  list<mixed>  $params
     */
    private function compileTree(mixed $node, callable $leafCompiler, array &$params): string
    {
        $kind = $node[0];

        if ($kind === 'leaf') {
            /** @var list<mixed> $leaf */
            $leaf = $node[1];

            return $leafCompiler(Domain::fromArray($leaf));
        }

        if ($kind === '!') {
            return 'NOT ('.$this->compileTree($node[1], $leafCompiler, $params).')';
        }

        if ($kind === '&') {
            return '('.$this->compileTree($node[1], $leafCompiler, $params)
                .' AND '.$this->compileTree($node[2], $leafCompiler, $params).')';
        }

        if ($kind === '|') {
            return '('.$this->compileTree($node[1], $leafCompiler, $params)
                .' OR '.$this->compileTree($node[2], $leafCompiler, $params).')';
        }

        throw new \InvalidArgumentException('Unknown domain node '.json_encode($kind).'.');
    }
}
