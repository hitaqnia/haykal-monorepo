<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Tenancy\Concerns;

use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Core\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Marks an Eloquent model as owned by a tenant.
 *
 * Using this trait causes Haykal to:
 *   1. Apply {@see TenantScope} as a global scope — queries are
 *      automatically filtered to the active tenant (rows with a NULL
 *      tenant foreign key remain visible as shared records).
 *   2. Populate the tenant foreign key column from
 *      `Tenancy::getTenantId()` on `creating` when the model does not
 *      set it explicitly and a tenant is active.
 *   3. Expose a `tenant()` BelongsTo relation.
 *
 * Each using model declares:
 *
 *     protected string $tenantModel = Agency::class;
 *     protected string $tenantForeignKey = 'agency_id';   // optional
 *
 * `$tenantForeignKey` defaults to {@see TenantScope::FOREIGN_KEY}
 * (`tenant_id`) for applications where every tenanted table shares one
 * column name. Applications with multiple tenant types (for example,
 * Agency and DevelopmentCompany) override it per-model to match the
 * actual FK column — `agency_id` on `properties`, `developer_id` on
 * `projects`. Only one tenant type is active per panel/request; the
 * scope always reads the current active id from `Tenancy` and applies
 * it to whichever column the model declares.
 *
 * Models that need dynamic resolution may override
 * `tenantRelationModel()` and/or `getTenantForeignKey()`.
 */
trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $column = $model->getTenantForeignKey();
            $tenantId = Tenancy::getTenantId();

            if ($tenantId !== null && empty($model->getAttribute($column))) {
                $model->setAttribute($column, $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo($this->tenantRelationModel(), $this->getTenantForeignKey());
    }

    /**
     * Resolve the concrete Tenant model class this model belongs to.
     *
     * Default implementation reads the `$tenantModel` property. Override
     * this method for dynamic resolution (e.g., to read from config).
     */
    protected function tenantRelationModel(): string
    {
        if (property_exists($this, 'tenantModel')) {
            /** @var string $target */
            $target = $this->tenantModel;

            return $target;
        }

        throw new RuntimeException(sprintf(
            'Model %s uses HasTenant but does not declare a tenant model. '.
            'Add `protected string $tenantModel = YourTenant::class;` or override tenantRelationModel().',
            static::class,
        ));
    }

    /**
     * Column on this model that references the active tenant.
     *
     * Defaults to the package-wide convention (`tenant_id`). Override
     * by declaring `protected string $tenantForeignKey = '<column>';`
     * on the using model — required when different models belong to
     * different tenant types (`agency_id`, `developer_id`, …).
     */
    public function getTenantForeignKey(): string
    {
        return property_exists($this, 'tenantForeignKey')
            ? $this->tenantForeignKey
            : TenantScope::FOREIGN_KEY;
    }
}
