<?php

declare(strict_types=1);

use Velm\Support\VelmDatetime;

test('nowUtc returns utc formatted timestamp', function (): void {
    $now = VelmDatetime::nowUtc();
    $parsed = new DateTimeImmutable($now, new DateTimeZone('UTC'));

    expect($now)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/')
        ->and($parsed->format('Y-m-d H:i:s'))->toBe($now);
});

test('fromUtc converts to company timezone', function (): void {
    expect(VelmDatetime::fromUtc('2026-06-03 12:00:00', 'America/New_York'))
        ->toBe('2026-06-03 08:00:00');
});

test('toUtc converts from company timezone', function (): void {
    expect(VelmDatetime::toUtc('2026-06-03 08:00:00', 'America/New_York'))
        ->toBe('2026-06-03 12:00:00');
});

test('invalid timezone falls back to utc', function (): void {
    expect(VelmDatetime::normalizeTimezone('Not/A_Zone'))->toBe('UTC');
});
