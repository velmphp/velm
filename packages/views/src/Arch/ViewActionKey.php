<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

final class ViewActionKey
{
    public static function fromLabel(string $label): string
    {
        $normalized = strtolower(trim($label));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim($slug, '-');
    }
}
