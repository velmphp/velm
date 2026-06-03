<?php

declare(strict_types=1);

namespace Velm\Company;

use Illuminate\Http\Request;

final class CompanyCookie
{
    public const string NAME = 'velm_company';

    public static function companyIdFromRequest(Request $request): ?int
    {
        $raw = $request->cookie(self::NAME);

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = filter_var($raw, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : null;
    }
}
