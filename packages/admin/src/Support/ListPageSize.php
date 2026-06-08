<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

final class ListPageSize
{
    /** Show every record on one page. */
    public const ALL = 0;

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::sizes() as $size) {
            $options[] = [
                'value' => $size,
                'label' => (string) $size,
            ];
        }

        $options[] = [
            'value' => self::ALL,
            'label' => 'All',
        ];

        return $options;
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return [...self::sizes(), self::ALL];
    }

    public static function default(): int
    {
        if (! function_exists('config')) {
            return 10;
        }

        $configured = config('velm.list_page_size');

        return self::normalize(is_numeric($configured) ? (int) $configured : 10);
    }

    public static function normalize(int $size): int
    {
        return in_array($size, self::values(), true) ? $size : self::default();
    }

    public static function isAll(int $size): bool
    {
        return $size === self::ALL;
    }

    public static function effectivePerPage(int $size, int $total): int
    {
        if (self::isAll($size)) {
            return max(1, $total);
        }

        return max(1, $size);
    }

    /**
     * @return list<int>
     */
    private static function sizes(): array
    {
        if (! function_exists('config')) {
            return [10, 25, 50, 100];
        }

        $configured = config('velm.list_page_sizes');

        if (! is_array($configured)) {
            return [10, 25, 50, 100];
        }

        $sizes = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => max(1, (int) $value),
            $configured,
        ))));

        sort($sizes);

        return $sizes !== [] ? $sizes : [10, 25, 50, 100];
    }
}
