<?php

declare(strict_types=1);

namespace Velm\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Velm\Environment;
use Velm\Modules\SystemAudit\AuditLoginLogger;

final class LogoutController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userId = $user?->getAuthIdentifier();
        $email = $user !== null && method_exists($user, 'getAttribute')
            ? (string) ($user->getAttribute('email') ?? '')
            : null;

        AuditLoginLogger::log(
            app(Environment::class),
            'logout',
            is_numeric($userId) ? (int) $userId : null,
            $email !== '' ? $email : null,
        );

        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('velm.auth.login');
    }
}
