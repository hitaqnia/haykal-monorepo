<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api;

use Dedoc\Scramble\ScrambleServiceProvider;
use HiTaqnia\Haykal\Api\HaykalApiServiceProvider;
use HiTaqnia\Haykal\Core\HaykalCoreServiceProvider;
use HiTaqnia\Haykal\Core\Identity\Models\Permission;
use HiTaqnia\Haykal\Core\Identity\Models\Role;
use HiTaqnia\Haykal\Core\Identity\Models\User;
use Huwiya\Facades\Huwiya;
use Huwiya\HuwiyaServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Shared Testbench base for haykal-api feature tests.
 *
 * Wires the full kernel+api stack, an in-memory SQLite database, and a
 * Huwiya test configuration that matches the `FakeHuwiyaIdP` fixture.
 */
abstract class ApiTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HuwiyaServiceProvider::class,
            PermissionServiceProvider::class,
            MediaLibraryServiceProvider::class,
            ScrambleServiceProvider::class,
            HaykalCoreServiceProvider::class,
            HaykalApiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', false);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('permission.teams', true);
        $app['config']->set('permission.testing', true);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permission.models.permission', Permission::class);

        $app['config']->set('huwiya.url', 'https://huwiya.test');
        $app['config']->set('huwiya.project_id', 'test-project');
        $app['config']->set('huwiya.validate_issuer', true);
        $app['config']->set('huwiya.validate_audience', true);

        $app['config']->set('auth.providers.users.model', User::class);

        // Register both Huwiya-driven guards the suite exercises.
        $app['config']->set('auth.guards.web', [
            'driver' => 'huwiya-web',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.guards.huwiya-api', [
            'driver' => 'huwiya-api',
            'provider' => 'users',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // haykal-core loads its migrations from the package; no extra action.
    }

    // -------------------------------------------------------------
    // Test helpers
    // -------------------------------------------------------------

    /**
     * Authenticate the given user against a Huwiya-driven guard for the
     * remainder of the test.
     */
    protected function authenticateAs(Authenticatable $user, string $guard = 'huwiya-api'): Authenticatable
    {
        return Huwiya::actingAs($user, $guard);
    }

    /**
     * Assert that the response carries the Haykal success envelope.
     */
    protected function assertApiSuccess(TestResponse $response, int $code = 200): void
    {
        $response->assertStatus($code);
        $response->assertJsonPath('success', 1);
        $response->assertJsonPath('code', $code);
        $response->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors',
        ]);
        $response->assertJsonPath('errors', null);
    }

    /**
     * Assert that the response carries the Haykal error envelope with the
     * given code. `$expectedHttpStatus` defaults to the business code but
     * may be overridden for business errors (> 999) that map to HTTP 409.
     */
    protected function assertApiError(TestResponse $response, int $code, ?int $expectedHttpStatus = null): void
    {
        $response->assertStatus($expectedHttpStatus ?? $code);
        $response->assertJsonPath('success', 0);
        $response->assertJsonPath('code', $code);
        $response->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors',
        ]);
        $response->assertJsonPath('data', null);
    }
}
