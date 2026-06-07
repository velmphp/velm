<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\View;
use Velm\Admin\Http\Middleware\ShareVelmMenuContext;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Registry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('menu middleware does not share velm menu on unrelated routes', function (): void {
    $this->get('/');

    expect(View::shared('velmMenu'))->toBeNull();
});

test('shell context omits companies when res.company model is unavailable', function (): void {
    $base = app(Environment::class);
    $env = new Environment($base->connection, new Registry, 1);

    $method = new ReflectionMethod(ShareVelmMenuContext::class, 'shellContext');
    $method->setAccessible(true);

    $context = $method->invoke(null, $env);

    expect($context['companies'])->toBe([])
        ->and($context['current_company_name'])->toBe('');
});

test('menu middleware shares velm shell on file library routes', function (): void {
    app(\Velm\Framework\VelmManager::class)->install('file_manager');
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $this->get('/web/files/library');

    expect(view()->shared('velmShell'))->toBeArray()
        ->and(view()->shared('velmMenu'))->toBeArray();
});
