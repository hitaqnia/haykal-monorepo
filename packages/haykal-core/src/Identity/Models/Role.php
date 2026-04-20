<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Models;

use HiTaqnia\Haykal\Core\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Haykal Role model: ULID-keyed + tenant-scoped.
 *
 * Apps must configure Spatie to use this model by setting
 * `permission.models.role` to this class in their published
 * `config/permission.php`.
 */
#[ScopedBy(TenantScope::class)]
class Role extends SpatieRole
{
    use HasUlids;

    protected $fillable = [
        'name',
        'guard_name',
    ];
}
