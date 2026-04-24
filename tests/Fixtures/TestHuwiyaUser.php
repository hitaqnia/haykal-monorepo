<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Fixtures;

use HiTaqnia\Haykal\Core\Identity\Models\BaseHuwiyaUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Concrete `BaseHuwiyaUser` subclass used only by the monorepo's test suite.
 *
 * Mirrors the structure a real consuming application would adopt: extend the
 * abstract Huwiya base, wire a `HasFactory` factory, and nothing else. Not
 * shipped with the published package.
 */
final class TestHuwiyaUser extends BaseHuwiyaUser
{
    use HasFactory;

    protected $table = 'users';

    protected static function newFactory(): Factory
    {
        return TestHuwiyaUserFactory::new();
    }
}
