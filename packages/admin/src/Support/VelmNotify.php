<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

final class VelmNotify
{
    /**
     * @param  array{type: string, title: string, body?: string|null}  $payload
     */
    public static function flash(string $type, string $title, ?string $body = null): void
    {
        session()->flash('velm_notify', [
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }
}
