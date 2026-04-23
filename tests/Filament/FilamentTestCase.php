<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use HiTaqnia\Haykal\Core\HaykalCoreServiceProvider;
use HiTaqnia\Haykal\Core\Identity\Models\Permission;
use HiTaqnia\Haykal\Core\Identity\Models\Role;
use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Filament\HaykalFilamentServiceProvider;
use Huwiya\HuwiyaServiceProvider;
use LaraZeus\SpatieTranslatable\SpatieTranslatableServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Shared Testbench base for haykal-filament feature tests.
 */
abstract class FilamentTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HuwiyaServiceProvider::class,
            PermissionServiceProvider::class,
            MediaLibraryServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            SpatieTranslatableServiceProvider::class,
            HaykalCoreServiceProvider::class,
            HaykalFilamentServiceProvider::class,
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

        $app['config']->set('permission.teams', true);
        $app['config']->set('permission.testing', true);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permission.models.permission', Permission::class);

        $app['config']->set('huwiya.url', 'https://huwiya.test');
        $app['config']->set('huwiya.project_id', 'test-project');

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.guards.web', [
            'driver' => 'huwiya-web',
            'provider' => 'users',
        ]);

        $app['config']->set('app.supported_locales', ['en', 'ar']);
    }

    protected function defineDatabaseMigrations(): void
    {
        // haykal-core auto-loads its migrations.
    }
}
