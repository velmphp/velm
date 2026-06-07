<?php

declare(strict_types=1);

namespace Velm\Ui\Support;

use Velm\Environment;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;

final class RelationalInitials
{
    /**
     * @return list<array{id: int, label: string}>
     */
    public static function many2manyChips(Environment $env, Many2manyField $field, mixed $value): array
    {
        $ids = self::normalizeIds($value);
        if ($ids === []) {
            return [];
        }

        $rows = $env->browse($field->comodel, $ids)->read();
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = (string) ($row['display_name'] ?? $row['id']);
        }

        $chips = [];
        foreach ($ids as $id) {
            $chips[] = ['id' => $id, 'label' => $byId[$id] ?? (string) $id];
        }

        return $chips;
    }

    /**
     * @return list<array{id: int, name: string, mimetype: string, thumbnail_url: string, download_url: string}>
     */
    public static function attachmentChips(Environment $env, mixed $value, bool $multi): array
    {
        $ids = $multi
            ? self::normalizeIds($value)
            : (($value !== null && $value !== '' && $value !== false) ? [(int) $value] : []);

        if ($ids === []) {
            return [];
        }

        $rows = $env->browse('ir.attachment', $ids)->read(['name', 'mimetype', 'type', 'url', 'file_size', 'public']);
        $byId = [];

        foreach ($rows as $row) {
            $chip = \Velm\Support\AttachmentRow::toArray($row);
            $byId[(int) $chip['id']] = [
                'id' => (int) $chip['id'],
                'name' => (string) ($chip['name'] !== '' ? $chip['name'] : ($row['display_name'] ?? $chip['id'])),
                'mimetype' => (string) $chip['mimetype'],
                'thumbnail_url' => (string) $chip['thumbnail_url'],
                'download_url' => (string) $chip['download_url'],
            ];
        }

        $chips = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $chips[] = $byId[$id];
            }
        }

        return $chips;
    }

    /**
     * @param  list<array{name: string, label: string}>  $columns
     * @return list<array<string, mixed>>
     */
    public static function one2manyRows(Environment $env, One2manyField $field, mixed $value, array $columns = []): array
    {
        $ids = self::normalizeIds($value);
        if ($ids === []) {
            return [];
        }

        $readFields = ['display_name'];
        foreach ($columns as $col) {
            $readFields[] = $col['name'];
        }
        $readFields = array_values(array_unique($readFields));

        $rows = $env->browse($field->comodel, $ids)->read($readFields);

        return array_map(
            static function (array $row): array {
                $out = ['id' => (int) $row['id'], 'label' => (string) ($row['display_name'] ?? $row['name'] ?? $row['id'])];
                foreach ($row as $key => $val) {
                    if ($key === 'id' || $key === 'display_name') {
                        continue;
                    }
                    $out[$key] = $val;
                }

                return $out;
            },
            $rows,
        );
    }

    /**
     * @return list<int>
     */
    public static function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(intval(...), array_filter($value, static fn (mixed $v): bool => $v !== null && $v !== '')));
    }
}
