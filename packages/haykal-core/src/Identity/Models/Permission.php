<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Haykal Permission model: ULID-keyed.
 *
 * Apps must configure Spatie to use this model by setting
 * `permission.models.permission` to this class in their published
 * `config/permission.php`.
 */
class Permission extends SpatiePermission
{
    use HasUlids;
}
