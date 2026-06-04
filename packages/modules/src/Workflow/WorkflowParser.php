<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

final class WorkflowParser
{
    /**
     * @return array<string, mixed>
     */
    public static function parse(string|array $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw !== '' ? $raw : '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadJson(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
