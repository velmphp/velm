<?php

declare(strict_types=1);

use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('artisan velm module list shows partners module', function (): void {
    $this->artisan('velm:module:list')
        ->assertSuccessful();
});

test('artisan velm seed runs without error', function (): void {
    $this->artisan('velm:seed')
        ->assertSuccessful();
});

test('artisan velm db status exits successfully', function (): void {
    $this->artisan('velm:db:status')
        ->assertSuccessful();
});

test('artisan velm cron run exits successfully', function (): void {
    $this->artisan('velm:cron:run')
        ->assertSuccessful();
});
