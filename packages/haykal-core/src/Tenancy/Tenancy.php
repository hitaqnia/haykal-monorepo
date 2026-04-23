<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Tenancy;

/**
 * Process-local tenant context resolver.
 *
 * The active tenant id is stored in the container so that middlewares set it
 * once per request, query scopes read it downstream, and tests isolate
 * tenants via container rebinding. Returns null when no tenant is active
 * (e.g., console commands, jobs that opted out, unauthenticated requests).
 */
final class Tenancy
{
    private const CONTAINER_KEY = 'tenant.id';

    public static function setTenantId(string $tenantId): void
    {
        app()->instance(self::CONTAINER_KEY, $tenantId);
    }

    public static function getTenantId(): ?string
    {
        return app()->has(self::CONTAINER_KEY) ? app(self::CONTAINER_KEY) : null;
    }

    public static function clear(): void
    {
        app()->forgetInstance(self::CONTAINER_KEY);
    }
}
