<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class HaykalCoreServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'haykal-core-migrations');

        $this->registerMiddlewareAlias();
    }

    /**
     * Register short route-middleware aliases for every middleware shipped
     * by haykal-core so apps can slot them into their own middleware groups
     * without depending on the full class path.
     */
    private function registerMiddlewareAlias(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware(
            'haykal.permissions.team',
            Http\Middlewares\PermissionsTeamMiddleware::class,
        );

        $router->aliasMiddleware(
            'haykal.user.locale',
            Http\Middlewares\SetUserLocaleMiddleware::class,
        );
    }
}
