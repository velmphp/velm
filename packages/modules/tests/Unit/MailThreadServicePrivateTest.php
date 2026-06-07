<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\Mail\MailThreadService;
use Velm\Registry;

test('mail thread service authorName falls back to uid without res.users model', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);
    $method = new ReflectionMethod(MailThreadService::class, 'authorName');
    $method->setAccessible(true);

    expect($method->invoke(null, $env, 42))->toBe('42');
});

test('mail thread service bodyToHtml returns empty string for empty input', function (): void {
    $method = new ReflectionMethod(MailThreadService::class, 'bodyToHtml');
    $method->setAccessible(true);

    expect($method->invoke(null, ''))->toBe('');
});

test('mail thread service isFollowing returns false without follower model', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);
    $method = new ReflectionMethod(MailThreadService::class, 'isFollowing');
    $method->setAccessible(true);

    expect($method->invoke(null, $env, 'it.change', 1, 1))->toBeFalse();
});

test('mail thread service followerCount returns zero without follower model', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);
    $method = new ReflectionMethod(MailThreadService::class, 'followerCount');
    $method->setAccessible(true);

    expect($method->invoke(null, $env, 'it.change', 1))->toBe(0);
});
