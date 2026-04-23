<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core;

use HiTaqnia\Haykal\Core\HaykalCoreServiceProvider;
use HiTaqnia\Haykal\Core\Identity\Models\Permission;
use HiTaqnia\Haykal\Core\Identity\Models\Role;
use HiTaqnia\Haykal\Core\Identity\Models\User;
use Huwiya\HuwiyaServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Shared Testbench base for haykal-core feature tests.
 *
 * Sets up a Laravel app with haykal-core and its ecosystem dependencies
 * wired in, a sqlite in-memory database, and Huwiya pointed at a
 * well-known test issuer so fake JWTs from `FakeHuwiyaIdP` validate.
 */
abstract class CoreTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HuwiyaServiceProvider::class,
            PermissionServiceProvider::class,
            MediaLibraryServiceProvider::class,
            HaykalCoreServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Spatie permission: enable teams + testing fix for sqlite.
        $app['config']->set('permission.teams', true);
        $app['config']->set('permission.testing', true);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permission.models.permission', Permission::class);

        // Huwiya: point at the fake issuer the tests control.
        $app['config']->set('huwiya.url', 'https://huwiya.test');
        $app['config']->set('huwiya.project_id', 'test-project');
        $app['config']->set('huwiya.validate_issuer', true);
        $app['config']->set('huwiya.validate_audience', true);

        // Auth: use the haykal User as the default provider model.
        $app['config']->set('auth.providers.users.model', User::class);

        // Resolve factory names for the haykal User model.
        Factory::guessFactoryNamesUsing(fn (string $modelName) => null);
    }

    protected function defineDatabaseMigrations(): void
    {
        // haykal-core ships its own users migration — don't layer Laravel's
        // default on top. Migrations are picked up via loadMigrationsFrom
        // in the service provider.
    }
}
