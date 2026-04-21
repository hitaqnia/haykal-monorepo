<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Http\Middlewares;

use Closure;
use Filament\Facades\Filament;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Filament\HaykalFilamentServiceProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Propagate the Filament-resolved tenant into Haykal's tenant context.
 *
 * When a Filament panel has tenancy enabled, Filament resolves the tenant
 * from the URL (e.g., `{tenant-slug}`) and exposes it via
 * `Filament::getTenant()`. This middleware forwards its primary key into
 * {@see Tenancy::setTenantId()} so every downstream consumer — Eloquent
 * scopes, permission team context, media library paths — sees the same
 * active tenant without having to inspect the Filament facade.
 *
 * Slot this middleware inside the panel's tenant-middleware stack so it
 * runs after Filament has resolved the tenant and before any tenant-scoped
 * query executes. Registered under the alias `haykal.filament.tenancy` by
 * {@see HaykalFilamentServiceProvider}.
 */
final class FilamentTenancyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant !== null) {
            Tenancy::setTenantId((string) $tenant->getKey());
        }

        return $next($request);
    }
}
