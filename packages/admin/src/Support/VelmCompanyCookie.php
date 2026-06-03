<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Company\CompanyCookie;

/**
 * @deprecated Use {@see CompanyCookie} instead.
 */
final class VelmCompanyCookie
{
    public const string NAME = CompanyCookie::NAME;

    public static function companyIdFromRequest(\Illuminate\Http\Request $request): ?int
    {
        return CompanyCookie::companyIdFromRequest($request);
    }
}
