<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

final class ViewOp
{
    /**
     * @param  list<mixed>  $target
     * @return array<string, mixed>
     */
    public static function remove(array $target): array
    {
        return ['op' => 'remove', 'target' => $target];
    }

    /**
     * @param  list<mixed>  $target
     * @return array<string, mixed>
     */
    public static function set(array $target, mixed $value): array
    {
        return ['op' => 'set', 'target' => $target, 'value' => $value];
    }

    /**
     * @param  list<mixed>  $target
     * @return array<string, mixed>
     */
    public static function replace(array $target, mixed $value): array
    {
        return ['op' => 'replace', 'target' => $target, 'value' => $value];
    }

    /**
     * @param  list<mixed>  $target
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    public static function update(array $target, array $attrs): array
    {
        return ['op' => 'update', 'target' => $target, 'value' => $attrs];
    }

    /**
     * @param  list<mixed>  $target
     * @return array<string, mixed>
     */
    public static function after(array $target, mixed $value): array
    {
        return ['op' => 'after', 'target' => $target, 'value' => $value];
    }

    /**
     * @param  list<mixed>  $target
     * @return array<string, mixed>
     */
    public static function before(array $target, mixed $value): array
    {
        return ['op' => 'before', 'target' => $target, 'value' => $value];
    }
}
