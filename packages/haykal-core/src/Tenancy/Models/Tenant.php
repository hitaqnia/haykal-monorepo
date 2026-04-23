<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Abstract base model for application tenants.
 *
 * Concrete tenants in consuming apps (e.g., Complex, Organization, Workspace)
 * should extend this class. Haykal deliberately ships no tenant migration —
 * the tenant table's columns are application-specific — but the base class
 * establishes the conventions every Haykal app shares: ULID primary keys,
 * soft deletes, and a mass-assignable `name` column.
 *
 * Apps typically add their own columns (slug, logo, subdomain, timezone,
 * etc.) and relations (members, units, projects) on the concrete subclass.
 */
abstract class Tenant extends Model
{
    use HasUlids;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
    ];
}
