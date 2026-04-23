<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Http\Middlewares;

use Closure;
use Filament\Facades\Filament;
use HiTaqnia\Haykal\Filament\HaykalFilamentServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the active Filament panel behind a `<panel-id>.access` authorization
 * check.
 *
 * Every Haykal panel is expected to register a matching Gate (typically in
 * the app's `AuthServiceProvider`) that evaluates whether the authenticated
 * user belongs on that panel — for example "user is an employee of the
 * active tenant" for an operations panel. Failing the check aborts with
 * 403 before any resource query executes.
 *
 * Registered under the alias `haykal.filament.access` by
 * {@see HaykalFilamentServiceProvider}.
 */
final class AccessCheckingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $panelId = Filament::getId();

        if ($panelId !== null && Gate::denies("{$panelId}.access")) {
            abort(403);
        }

        return $next($request);
    }
}
