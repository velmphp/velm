<?php

declare(strict_types=1);

namespace Velm\Support;

/**
 * Naive {@code Y-m-d H:i:s} timestamps: UTC in storage, company timezone at the UI boundary.
 */
final class VelmDatetime
{
    private const string FORMAT = 'Y-m-d H:i:s';

    public static function nowUtc(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(self::FORMAT);
    }

    public static function normalizeTimezone(string $timezone): string
    {
        if ($timezone === '') {
            return 'UTC';
        }

        try {
            new \DateTimeZone($timezone);

            return $timezone;
        } catch (\Exception) {
            return 'UTC';
        }
    }

    public static function fromUtc(mixed $value, string $timezone): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $utc = self::parseUtc((string) $value);
        $local = $utc->setTimezone(new \DateTimeZone(self::normalizeTimezone($timezone)));

        return $local->format(self::FORMAT);
    }

    public static function toUtc(mixed $value, string $timezone): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $local = new \DateTimeImmutable((string) $value, new \DateTimeZone(self::normalizeTimezone($timezone)));

        return $local->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMAT);
    }

    private static function parseUtc(string $value): \DateTimeImmutable
    {
        $normalized = str_replace('T', ' ', $value);
        $normalized = preg_replace('/\.\d+$/', '', $normalized) ?? $normalized;
        $normalized = rtrim($normalized, 'Z');

        return new \DateTimeImmutable($normalized, new \DateTimeZone('UTC'));
    }
}
