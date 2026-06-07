<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\FileManager\FileManagerCompanyScope;
use Velm\Registry;

test('defaultCompanyId returns null without res.company model', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    expect(FileManagerCompanyScope::defaultCompanyId($env))->toBeNull();
});

test('envForCreate returns same env when company cannot be stamped', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    expect(FileManagerCompanyScope::envForCreate($env))->toBe($env);
});

test('stampCompanyId uses first allowed company when active and default are unset', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(\Velm\Modules\Base\Models\Company::class);
        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry, uid: Environment::SUPERUSER_ID);
        (new \Velm\Schema\SchemaBuilder($connection))->syncRegistry($registry);
        $env->model('res.company')->create(['name' => 'Allowed Co']);

        return $env->withContext(['company_id' => null]);
    });

    expect(FileManagerCompanyScope::stampCompanyId($env))->toBe(1);
});
