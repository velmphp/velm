<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

final class ListPagination
{
    public const SIMPLE = 'simple';

    public const FULL = 'full';

    public static function resolveStyle(?string $fromArch = null): string
    {
        $style = is_string($fromArch) && $fromArch !== '' ? $fromArch : null;

        if ($style === null && function_exists('config')) {
            $configured = config('velm.list_pagination');

            if (is_string($configured) && $configured !== '') {
                $style = $configured;
            }
        }

        $style ??= self::SIMPLE;

        return in_array($style, [self::SIMPLE, self::FULL], true) ? $style : self::SIMPLE;
    }

    public static function viewForStyle(string $style): string
    {
        return $style === self::FULL
            ? 'velm-ui::pagination.full'
            : 'velm-ui::pagination.simple';
    }
}
