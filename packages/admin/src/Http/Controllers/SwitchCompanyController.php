<?php

declare(strict_types=1);

namespace Velm\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Velm\Company\CompanyCookie;
use Velm\Environment;
use Velm\Admin\Support\VelmPanel;
use Velm\Framework\VelmManager;

final class SwitchCompanyController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $redirect = redirect()->to(
            $request->headers->get('referer') ?: VelmPanel::getUrl(),
        );

        $base = app(VelmManager::class)->environment();
        $uid = VelmPanel::auth()->id();
        $uid = is_numeric($uid) ? (int) $uid : Environment::SUPERUSER_ID;
        $env = new Environment($base->connection, $base->registry, $uid);

        $raw = $request->input('company_id');

        if ($raw === null || $raw === '') {
            if ($env->allowsAllCompaniesMode()) {
                return $redirect->withoutCookie(CompanyCookie::NAME);
            }

            $default = $env->userDefaultCompanyId();

            if ($default !== null) {
                return $redirect->cookie(CompanyCookie::NAME, (string) $default, 60 * 24 * 365);
            }

            return $redirect->withoutCookie(CompanyCookie::NAME);
        }

        $id = filter_var($raw, FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            return $redirect->withoutCookie(CompanyCookie::NAME);
        }

        $allowed = $env->allowedCompanyIds();

        if (! $env->isSuperuser() && ! in_array($id, $allowed, true)) {
            return $redirect->with('error', __('You cannot switch to that company.'));
        }

        if (! $env->companyExists($id)) {
            return $redirect->with('error', __('Company not found.'));
        }

        return $redirect->cookie(CompanyCookie::NAME, (string) $id, 60 * 24 * 365);
    }
}
