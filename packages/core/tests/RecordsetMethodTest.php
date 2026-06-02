<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\CountryExtension;
use Velm\Core\Tests\Support\CountryBadSuperExtension;
use Velm\Core\Tests\Support\CountryGreetingTopExtension;
use Velm\Core\Tests\Support\CountryLegacySuperExtension;
use Velm\Core\Tests\Support\CountryOnlySuperExtension;
use Velm\Core\Tests\Support\CountrySuffixExtension;
use Velm\Core\Tests\Support\CountryTagExtension;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Models\Model;
use Velm\Recordset\Recordset;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

/**
 * @param  list<class-string<Model>>  $extraExtensions
 */
function recordMethodEnvironment(
    bool $withExtension = true,
    array $extraExtensions = [],
): Environment {
    return Registry::using(function (Registry $registry) use ($withExtension, $extraExtensions): Environment {
        $registry->register(Country::class);

        if ($withExtension) {
            $registry->registerExtension(CountryExtension::class);
        }

        foreach ($extraExtensions as $extensionClass) {
            $registry->registerExtension($extensionClass);
        }

        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });
}

test('base model instance methods work without extensions', function (): void {
    $env = recordMethodEnvironment(withExtension: false);
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);

    expect($country->greetingLabel())->toBe('Hello Belgium')
        ->and($env->registry->modelClass('res.country'))->toBe(Country::class);
});

test('recordset dispatches instance methods on the effective model class', function (): void {
    $env = recordMethodEnvironment();
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
    ]);

    expect($country->greetingLabel())->toBe('Hello Belgium (EU)');
});

test('topmost MRO implementor wins when multiple layers override the same method', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [CountryGreetingTopExtension::class]);
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
    ]);

    expect($country->greetingLabel())->toBe('{Hello Belgium (EU)}')
        ->and($env->registry->modelClass('res.country'))->toBe(CountryGreetingTopExtension::class);
});

test('instance super walks past middle extensions that do not implement the method', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [
        CountryTagExtension::class,
        CountryGreetingTopExtension::class,
    ]);
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
        'tag' => 'benelux',
    ]);

    expect($country->greetingLabel())->toBe('{Hello Belgium (EU)}');
});

test('record method resolves the nearest implementor when effective class lacks the method', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [CountryTagExtension::class]);
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
        'tag' => 'benelux',
    ]);

    expect($country->greetingLabel())->toBe('Hello Belgium (EU)')
        ->and(CountryTagExtension::isRecordMethod('greetingLabel'))->toBeFalse()
        ->and(CountryExtension::isRecordMethod('greetingLabel'))->toBeTrue();
});

test('record methods accept extra arguments after the recordset', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [CountrySuffixExtension::class]);
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'suffix' => 'EU',
    ]);

    expect($country->labelWithSuffix())->toBe('Belgium!EU')
        ->and($country->labelWithSuffix(' / '))->toBe('Belgium / EU');
});

test('legacy super(__FUNCTION__, recordset) works for instance methods', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [CountryLegacySuperExtension::class]);
    $country = $env->model('res.country')->create(['name' => 'X', 'code' => 'XX']);

    expect($country->greetingLabel())->toBe('legacy:Hello X');
});

test('behavior returns a singleton per model class', function (): void {
    expect(Country::behavior())->toBe(Country::behavior())
        ->and(CountryExtension::behavior())->not->toBe(Country::behavior());
});

test('isRecordMethod rejects static and Model base methods', function (): void {
    expect(Country::isRecordMethod('greetingLabel'))->toBeTrue()
        ->and(Country::isRecordMethod('displayNameFor'))->toBeFalse()
        ->and(Country::isRecordMethod('name'))->toBeFalse()
        ->and(Country::isRecordMethod('fields'))->toBeFalse()
        ->and(Country::isRecordMethod('behavior'))->toBeFalse()
        ->and(Country::isRecordMethod('missing'))->toBeFalse();
});

test('ensureOne rejects empty recordsets', function (): void {
    $env = recordMethodEnvironment();
    $env->model('res.country')->ensureOne();
})->throws(InvalidArgumentException::class, 'Expected a single record');

test('ensureOne rejects multi-record recordsets', function (): void {
    $env = recordMethodEnvironment();
    $first = $env->model('res.country')->create(['name' => 'A', 'code' => 'AA']);
    $second = $env->model('res.country')->create(['name' => 'B', 'code' => 'BB']);
    $multi = $env->browse('res.country', [$first->ids()[0], $second->ids()[0]]);

    $multi->ensureOne();
})->throws(InvalidArgumentException::class);

test('undefined record methods throw BadMethodCallException', function (): void {
    $env = recordMethodEnvironment();
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);

    $country->missingMethod();
})->throws(BadMethodCallException::class);

test('instance super throws when no parent implements the method', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [CountryOnlySuperExtension::class]);
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);

    $country->soloLabel();
})->throws(LogicException::class, 'no parent in the model MRO');

test('instance super without a recordset throws', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [CountryBadSuperExtension::class]);
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);

    $country->callsSuperWithoutRecordset();
})->throws(LogicException::class, 'Recordset');

test('static and instance super coexist on the same extension class', function (): void {
    $env = recordMethodEnvironment();
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
    ]);

    expect($country->read()[0]['display_name'])->toBe('Belgium [EU]')
        ->and($country->greetingLabel())->toBe('Hello Belgium (EU)');
});

test('read resolves displayNameFor from the nearest static hook in the MRO', function (): void {
    $env = recordMethodEnvironment(extraExtensions: [
        CountryTagExtension::class,
        CountryGreetingTopExtension::class,
    ]);
    $country = $env->model('res.country')->create([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
        'tag' => 'benelux',
    ]);

    expect($country->read()[0]['display_name'])->toBe('Belgium [EU] #benelux');
});

test('record dispatch requires an active registry on the environment', function (): void {
    $registry = new Registry;
    $registry->register(Country::class);
    $connection = PdoConnection::sqliteMemory();
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);

    expect(fn () => $country->greetingLabel())->not->toThrow(RuntimeException::class);
});
