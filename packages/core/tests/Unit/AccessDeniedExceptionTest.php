<?php

declare(strict_types=1);

use Velm\Exception\AccessDeniedException;

test('access denied forPermission includes uid or anonymous', function (): void {
    expect(AccessDeniedException::forPermission('res.partner', 'read', 42)->getMessage())
        ->toBe('Access denied: read on res.partner (uid=42)')
        ->and(AccessDeniedException::forPermission('res.partner', 'write', null)->getMessage())
        ->toBe('Access denied: write on res.partner (anonymous)');
});

test('access denied forCompanyScope includes uid or anonymous', function (): void {
    expect(AccessDeniedException::forCompanyScope('res.partner', 7)->getMessage())
        ->toContain('outside active company')
        ->and(AccessDeniedException::forCompanyScope('res.partner', null)->getMessage())
        ->toContain('(anonymous)');
});

test('access denied forCompanyMismatch includes uid or anonymous', function (): void {
    expect(AccessDeniedException::forCompanyMismatch('res.partner', 3)->getMessage())
        ->toContain('company mismatch')
        ->and(AccessDeniedException::forCompanyMismatch('res.partner', null)->getMessage())
        ->toContain('(anonymous)');
});
