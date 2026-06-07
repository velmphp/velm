<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Velm\Admin\Support\VelmCompanyCookie;
use Velm\Company\CompanyCookie;

test('velm company cookie delegates to company cookie helper', function (): void {
    expect(VelmCompanyCookie::NAME)->toBe(CompanyCookie::NAME);

    $request = Request::create('/', 'GET', [], [CompanyCookie::NAME => '42']);

    expect(VelmCompanyCookie::companyIdFromRequest($request))->toBe(42);
});
