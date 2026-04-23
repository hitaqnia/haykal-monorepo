<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Tenancy;

use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Eloquent scope that constrains queries to the currently active tenant.
 *
 * Applies when `Tenancy::getTenantId()` resolves; otherwise queries are
 * unrestricted. Records whose tenant FK is NULL are treated as shared
 * across all tenants and are always visible.
 *
 * The foreign key column is resolved in the following order:
 *   1. `$model->getTenantForeignKey()` — provided by the
 *      {@see HasTenant} trait
 *      and overridable per-model for applications with multiple
 *      tenant types (`agency_id`, `developer_id`, …).
 *   2. The package-wide default, {@see TenantScope::FOREIGN_KEY}
 *      (`tenant_id`).
 */
final class TenantScope implements Scope
{
    /** Default column on tenant-scoped tables that references the active tenant. */
    public const FOREIGN_KEY = 'tenant_id';

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Tenancy::getTenantId();

        if ($tenantId === null) {
            return;
        }

        $column = $model->qualifyColumn($this->foreignKeyFor($model));

        $builder->where(function (Builder $query) use ($column, $tenantId): void {
            $query->where($column, $tenantId)->orWhereNull($column);
        });
    }

    private function foreignKeyFor(Model $model): string
    {
        if (method_exists($model, 'getTenantForeignKey')) {
            /** @var string $column */
            $column = $model->getTenantForeignKey();

            return $column;
        }

        return self::FOREIGN_KEY;
    }
}
