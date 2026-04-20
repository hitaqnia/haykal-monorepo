<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Models;

use HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Haykal Role model: ULID-keyed.
 *
 * Tenant scoping is provided by Spatie's own team support (enabled via
 * `permission.teams = true`); the `team_id` column maps to Haykal's
 * active tenant through {@see PermissionsTeamMiddleware}.
 *
 * Apps must configure Spatie to use this model by setting
 * `permission.models.role` to this class in their published
 * `config/permission.php`.
 */
class Role extends SpatieRole
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
