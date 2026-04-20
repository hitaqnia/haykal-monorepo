<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Eloquent scope that constrains queries to the currently active tenant.
 *
 * Applies when `Tenancy::getTenantId()` resolves; otherwise queries are
 * unrestricted. Records whose `tenant_id` is NULL are treated as shared
 * across all tenants and are always visible.
 */
final class TenantScope implements Scope
{
    /** Column on tenant-scoped tables that references the active tenant. */
    public const FOREIGN_KEY = 'tenant_id';

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Tenancy::getTenantId();

        if ($tenantId === null) {
            return;
        }

        $column = $model->qualifyColumn(self::FOREIGN_KEY);

        $builder->where(function (Builder $query) use ($column, $tenantId): void {
            $query->where($column, $tenantId)->orWhereNull($column);
        });
    }
}
