<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Http\Middlewares;

use Closure;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wire Spatie's permission package to the active tenant.
 *
 * Spatie's `setPermissionsTeamId()` scopes role/permission lookups to a
 * team id — we forward the active Haykal tenant so that users carry the
 * correct permission set inside that tenant. Must run **after** the
 * middleware that resolves the active tenant.
 */
final class PermissionsTeamMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            setPermissionsTeamId(Tenancy::getTenantId());
        }

        return $next($request);
    }
}
