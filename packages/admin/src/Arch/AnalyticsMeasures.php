<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

final class AnalyticsMeasures
{
    /**
     * @param  list<string>  $measures
     * @return list<string>
     */
    public function aggregateFields(array $measures): array
    {
        return array_values(array_filter(
            $measures,
            static fn (string $measure): bool => $measure !== '__count',
        ));
    }

    /**
     * @param  list<string>  $measures
     * @return array<string, string>
     */
    public function labels(array $measures): array
    {
        $labels = [];

        foreach ($measures as $measure) {
            $labels[$measure] = $this->label($measure);
        }

        return $labels;
    }

    public function label(string $measure): string
    {
        if ($measure === '__count') {
            return 'Count';
        }

        if (! str_contains($measure, ':')) {
            return $measure;
        }

        [$field, $aggregate] = explode(':', $measure, 2);

        return ucfirst($field).' '.ucfirst($aggregate);
    }

    /**
     * @param  list<string>  $measures
     * @return array<string, int|float|null>
     */
    public function values(array $measures, array $group): array
    {
        $values = [];

        foreach ($measures as $measure) {
            $values[$measure] = $this->value($measure, $group);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    public function value(string $measure, array $group): int|float|null
    {
        if ($measure === '__count') {
            return (int) ($group['__count'] ?? 0);
        }

        if (! str_contains($measure, ':')) {
            return null;
        }

        [$field, $aggregate] = explode(':', $measure, 2);
        $key = $field.'_'.strtolower($aggregate);
        $raw = $group[$key] ?? null;

        if ($raw === null) {
            return null;
        }

        if ($aggregate === 'avg') {
            return is_numeric($raw) ? (float) $raw : null;
        }

        return is_numeric($raw) ? (int) $raw : null;
    }

    /**
     * @param  list<string>  $measures
     */
    public function primaryValue(array $measures, array $group): int|float
    {
        foreach ($measures as $measure) {
            $value = $this->value($measure, $group);

            if ($value !== null) {
                return $value;
            }
        }

        return (int) ($group['__count'] ?? 0);
    }
}
