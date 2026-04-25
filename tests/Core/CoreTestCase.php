<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core;

use HiTaqnia\Haykal\Core\HaykalCoreServiceProvider;
use HiTaqnia\Haykal\Tests\Fixtures\TestHuwiyaUser;
use HiTaqnia\Haykal\Tests\Fixtures\TestPermission;
use HiTaqnia\Haykal\Tests\Fixtures\TestRole;
use Huwiya\HuwiyaServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Shared Testbench base for haykal-core feature tests.
 *
 * haykal-core is a utility-only package — it ships no migrations or
 * models. The test base recreates a realistic schema by running the
 * reference migrations stored under `tests/Fixtures/migrations/`, wires
 * Spatie to the local Role/Permission fixtures, and points the default
 * auth provider at the fixture User.
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
        $app['config']->set('permission.models.role', TestRole::class);
        $app['config']->set('permission.models.permission', TestPermission::class);

        // Huwiya: point at the fake issuer the tests control.
        $app['config']->set('huwiya.url', 'https://huwiya.test');
        $app['config']->set('huwiya.project_id', 'test-project');
        $app['config']->set('huwiya.validate_issuer', true);
        $app['config']->set('huwiya.validate_audience', true);

        // Auth: use the fixture User as the default provider model.
        $app['config']->set('auth.providers.users.model', TestHuwiyaUser::class);

        // Prevent Laravel from guessing factory names — fixtures declare
        // their factory via `newFactory()` directly.
        Factory::guessFactoryNamesUsing(fn (string $modelName) => null);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Fixtures/migrations');
    }
}
