<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament;

use HiTaqnia\Haykal\Filament\Console\PublishThemeCommand;
use HiTaqnia\Haykal\Filament\Http\Middlewares\AccessCheckingMiddleware;
use HiTaqnia\Haykal\Filament\Http\Middlewares\FilamentTenancyMiddleware;
use HiTaqnia\Haykal\Filament\Http\Middlewares\SetPanelLocale;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class HaykalFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/haykal-filament-icons.php',
            'filament.icons',
        );
    }

    public function boot(): void
    {
        $this->registerMiddlewareAliases();
        $this->registerPublishables();
        $this->registerCommands();
    }

    /**
     * Expose haykal-filament middlewares under short route-middleware aliases.
     */
    private function registerMiddlewareAliases(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('haykal.filament.access', AccessCheckingMiddleware::class);
        $router->aliasMiddleware('haykal.filament.locale', SetPanelLocale::class);
        $router->aliasMiddleware('haykal.filament.tenancy', FilamentTenancyMiddleware::class);
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/haykal-filament-icons.php' => config_path('haykal-filament-icons.php'),
        ], 'haykal-filament-icons');

        $this->publishes([
            __DIR__.'/../resources/css/base-theme.css' => resource_path('css/haykal/base-theme.css'),
        ], 'haykal-filament-theme');

        $this->publishes([
            __DIR__.'/../stubs/panel-theme.css.stub' => resource_path('stubs/haykal/panel-theme.css.stub'),
        ], 'haykal-filament-stubs');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PublishThemeCommand::class,
        ]);
    }
}
