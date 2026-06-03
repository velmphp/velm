<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\Partner;
use Velm\Support\VelmDatetime;

function ormEnvironment(): Environment
{
    return Registry::using(function (Registry $registry): Environment {
        $registry->register(Country::class);
        $registry->register(Partner::class);

        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });
}

test('creates and reads a partner record', function (): void {
    $env = ormEnvironment();

    $partner = $env->model('res.partner')->create([
        'name' => 'Acme Corp',
        'active' => true,
    ]);

    $row = $partner->read()[0];

    expect($partner->ids())->toBe([1])
        ->and($row['id'])->toBe(1)
        ->and($row['name'])->toBe('Acme Corp')
        ->and($row['active'])->toBeTrue()
        ->and($row['country_id'])->toBeNull()
        ->and($row['display_name'])->toBe('Acme Corp')
        ->and($row['created_at'])->not->toBeNull()
        ->and($row['updated_at'])->not->toBeNull()
        ->and($row['created_at'])->toBe($row['updated_at']);
});

test('writes field values on existing records', function (): void {
    $env = ormEnvironment();
    $partner = $env->model('res.partner')->create(['name' => 'Before']);
    $before = $partner->read()[0];

    sleep(1);
    $partner->write(['name' => 'After']);
    $after = $partner->read()[0];

    expect($after['name'])->toBe('After')
        ->and($after['created_at'])->toBe($before['created_at'])
        ->and($after['updated_at'])->not->toBe($before['updated_at']);
});

test('datetimes are stored in utc and read in company timezone', function (): void {
    $env = ormEnvironment()->withContext(['timezone' => 'America/New_York']);
    $partner = $env->model('res.partner')->create(['name' => 'TZ Partner']);
    $id = $partner->ids()[0];

    $row = $env->browse('res.partner', [$id])->read(['created_at'])[0];
    $stored = $env->connection->fetchAll('SELECT created_at FROM res_partner WHERE id = ?', [$id])[0]['created_at'];

    expect($stored)->not->toBe($row['created_at'])
        ->and(VelmDatetime::toUtc($row['created_at'], 'America/New_York'))->toBe($stored);
});

test('write refreshes updated_at even when stale timestamps are submitted', function (): void {
    $env = ormEnvironment();
    $partner = $env->model('res.partner')->create(['name' => 'Stale TS']);
    $before = $partner->read()[0];

    sleep(1);
    $partner->write([
        'name' => 'Stale TS edited',
        'created_at' => $before['created_at'],
        'updated_at' => $before['updated_at'],
    ]);
    $after = $partner->read()[0];

    expect($after['name'])->toBe('Stale TS edited')
        ->and($after['created_at'])->toBe($before['created_at'])
        ->and($after['updated_at'])->not->toBe($before['updated_at']);
});

test('searches with ilike operator on text fields', function (): void {
    $env = ormEnvironment();
    $env->model('res.partner')->create(['name' => 'Acme Corp']);
    $env->model('res.partner')->create(['name' => 'Beta LLC']);

    $matches = $env->model('res.partner')->search([['name', 'ilike', '%acme%']]);

    expect($matches->count())->toBe(1)
        ->and($matches->read()[0]['name'])->toBe('Acme Corp');
});

test('searches with simple domains', function (): void {
    $env = ormEnvironment();
    $env->model('res.partner')->create(['name' => 'Active Co', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Inactive Co', 'active' => false]);

    $active = $env->model('res.partner')->search([['active', '=', true]]);

    expect($active->count())->toBe(1)
        ->and($active->read()[0]['name'])->toBe('Active Co');
});

test('searches with ilike and or groups', function (): void {
    $env = ormEnvironment();
    $env->model('res.partner')->create(['name' => 'Acme Corp', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Other LLC', 'active' => true]);

    $matches = $env->model('res.partner')->search([
        ['__or__', 'ilike', [
            ['name', 'ilike', '%acme%'],
        ]],
    ]);

    expect($matches->count())->toBe(1)
        ->and($matches->read()[0]['name'])->toBe('Acme Corp');
});

test('unlink removes records from the table', function (): void {
    $env = ormEnvironment();
    $first = $env->model('res.partner')->create(['name' => 'Keep']);
    $second = $env->model('res.partner')->create(['name' => 'Remove']);

    $env->browse('res.partner', [$second->ids()[0]])->unlink();

    expect($env->model('res.partner')->search([])->count())->toBe(1)
        ->and($first->read()[0]['name'])->toBe('Keep');
});

test('many2one stores foreign key', function (): void {
    $env = ormEnvironment();
    $us = $env->model('res.country')->create(['name' => 'United States', 'code' => 'US']);
    $partner = $env->model('res.partner')->create([
        'name' => 'Acme',
        'country_id' => $us->ids()[0],
    ]);

    expect($partner->read()[0]['country_id'])->toBe(1);
});

test('registry rejects duplicate model names', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);

        expect(fn () => $registry->register(Country::class))
            ->toThrow(RuntimeException::class);
    });
});
