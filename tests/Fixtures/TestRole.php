<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Test fixture mirroring the ULID-keyed Spatie Role the consuming
 * application declares in its own identity namespace. Not part of the
 * published haykal-core package.
 */
final class TestRole extends SpatieRole
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
