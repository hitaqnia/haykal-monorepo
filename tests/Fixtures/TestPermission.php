<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Test fixture mirroring the ULID-keyed Spatie Permission the consuming
 * application declares in its own identity namespace. Not part of the
 * published haykal-core package.
 */
final class TestPermission extends SpatiePermission
{
    use HasUlids;
}
