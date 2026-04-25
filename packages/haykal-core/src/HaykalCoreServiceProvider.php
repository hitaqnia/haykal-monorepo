<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * Package service provider for haykal-core.
 *
 * haykal-core is a utility package — it ships no migrations, no models,
 * and no publishable configuration. The provider's only responsibility is
 * to register the short route-middleware alias for the middleware the
 * package ships.
 *
 * Consuming apps own their User / Role / Permission models and the
 * accompanying migrations; haykal-core's middleware reads through the
 * active tenant without caring which concrete classes are in use.
 */
final class HaykalCoreServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerMiddlewareAlias();
    }

    private function registerMiddlewareAlias(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware(
            'haykal.permissions.team',
            Http\Middlewares\PermissionsTeamMiddleware::class,
        );
    }
}
