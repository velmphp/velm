<?php

declare(strict_types=1);

namespace Velm\Framework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Velm\Company\CompanyCookie;
use Velm\Environment;
use Velm\Framework\VelmManager;

final class BindVelmEnvironment
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldBind($request)) {
            return $next($request);
        }

        $existing = app()->bound(Environment::class) ? app(Environment::class) : null;
        $base = $existing ?? app(VelmManager::class)->environment();
        $uid = $this->resolveUid($request, $existing);

        $companyId = $base->resolveActiveCompanyId(CompanyCookie::companyIdFromRequest($request));

        $context = [];

        if ($companyId !== null) {
            $context['company_id'] = $companyId;
            $context['timezone'] = $base->withContext(['company_id' => $companyId])->resolveCompanyTimezone($companyId);
        }

        app()->instance(Environment::class, new Environment(
            $base->connection,
            $base->registry,
            $uid,
            $context,
        ));

        return $next($request);
    }

    private function shouldBind(Request $request): bool
    {
        $panelPath = (string) config('velm.panel_path', 'velm');

        return $request->is($panelPath.'*') || $request->is('api/*');
    }

    private function resolveUid(Request $request, ?Environment $existing): int
    {
        $auth = $request->user();

        if ($auth !== null) {
            $id = $auth->getAuthIdentifier();

            return is_numeric($id) ? (int) $id : Environment::SUPERUSER_ID;
        }

        if ($existing !== null && $existing->uid !== null && $existing->uid !== Environment::SUPERUSER_ID) {
            return $existing->uid;
        }

        return Environment::SUPERUSER_ID;
    }
}
