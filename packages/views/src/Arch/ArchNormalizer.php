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
            'form', 'detail' => self::normalizeSections($arch),
            'kanban' => self::normalizeKanban($arch),
            'graph' => self::normalizeGraph($arch),
            'pivot' => self::normalizePivot($arch),
            default => $arch,
        };
    }

    /**
     * @return list<string>
     */
    public static function supportedViewTypes(): array
    {
        return ['list', 'form', 'detail', 'kanban', 'graph', 'pivot'];
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
        return self::normalizeSections($arch);
    }

    public static function normalizeDetail(array $arch): array
    {
        return self::normalizeSections($arch);
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalizeSections(array $arch): array
    {
        $sections = [];

        foreach ($arch['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            if (isset($section['pages']) && is_array($section['pages'])) {
                foreach ($section['pages'] as $i => $page) {
                    if (! is_array($page)) {
                        continue;
                    }
                    $section['pages'][$i]['fields'] = self::normalizeFields($page['fields'] ?? []);
                }
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

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalizeKanban(array $arch): array
    {
        if (isset($arch['card']) && is_array($arch['card'])) {
            $card = $arch['card'];

            if (isset($card['fields'])) {
                $card['fields'] = self::normalizeFields($card['fields']);
            }

            if (isset($card['badges'])) {
                $card['badges'] = self::normalizeFields($card['badges']);
            }

            $arch['card'] = $card;
        }

        return $arch;
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalizeGraph(array $arch): array
    {
        if (isset($arch['measure']) && is_string($arch['measure'])) {
            $arch['measures'] = [$arch['measure']];
            unset($arch['measure']);
        }

        if (isset($arch['measures']) && is_string($arch['measures'])) {
            $arch['measures'] = [$arch['measures']];
        }

        if (isset($arch['measures']) && is_array($arch['measures'])) {
            $arch['measures'] = array_values(array_map(strval(...), $arch['measures']));
        }

        if (! isset($arch['chart']) || ! is_string($arch['chart']) || $arch['chart'] === '') {
            $arch['chart'] = 'bar';
        }

        return $arch;
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    public static function normalizePivot(array $arch): array
    {
        foreach (['rows', 'cols', 'measures'] as $key) {
            if (! isset($arch[$key])) {
                continue;
            }

            if (is_string($arch[$key])) {
                $arch[$key] = [$arch[$key]];

                continue;
            }

            if (is_array($arch[$key])) {
                $arch[$key] = array_values(array_map(strval(...), $arch[$key]));
            }
        }

        if (! isset($arch['measures']) || $arch['measures'] === []) {
            $arch['measures'] = ['__count'];
        }

        return $arch;
    }
}
