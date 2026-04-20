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
 *      `tenant_id` remain visible as shared records).
 *   2. Populate the `tenant_id` column from `Tenancy::getTenantId()` on
 *      `creating` when the model does not set it explicitly and a
 *      tenant is active.
 *   3. Expose a `tenant()` BelongsTo relation. Because the concrete
 *      Tenant model varies per application, each using model must
 *      declare the target class via a `$tenantModel` property:
 *
 *          protected string $tenantModel = Complex::class;
 *
 *      Models that need dynamic resolution may override
 *      `tenantRelationModel()` instead.
 */
trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $column = TenantScope::FOREIGN_KEY;
            $tenantId = Tenancy::getTenantId();

            if ($tenantId !== null && empty($model->getAttribute($column))) {
                $model->setAttribute($column, $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo($this->tenantRelationModel(), TenantScope::FOREIGN_KEY);
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
}
