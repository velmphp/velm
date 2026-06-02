<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

final class ArchNormalizer
{
    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalize(array $arch, string $viewType): array
    {
        return match ($viewType) {
            'list' => self::normalizeList($arch),
            'form' => self::normalizeForm($arch),
            default => $arch,
        };
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalizeList(array $arch): array
    {
        $arch['fields'] = self::normalizeFields($arch['fields'] ?? []);

        return $arch;
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalizeForm(array $arch): array
    {
        $sections = [];

        foreach ($arch['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $section['fields'] = self::normalizeFields($section['fields'] ?? []);
            $sections[] = $section;
        }

        $arch['sections'] = $sections;

        return $arch;
    }

    /**
     * @param  list<mixed>  $fields
     * @return list<array<string, mixed>>
     */
    public static function normalizeFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (is_string($field)) {
                $normalized[] = ['name' => $field];

                continue;
            }

            if (is_array($field) && isset($field['name']) && is_string($field['name'])) {
                $normalized[] = $field;
            }
        }

        return $normalized;
    }
}
