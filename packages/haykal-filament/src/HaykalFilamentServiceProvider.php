<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use HiTaqnia\Haykal\Filament\Console\PublishThemeCommand;
use HiTaqnia\Haykal\Filament\Http\Middlewares\FilamentTenancyMiddleware;
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

        $this->mergeConfigFrom(
            __DIR__.'/../config/mapbox.php',
            'mapbox',
        );
    }

    public function boot(): void
    {
        $this->registerMiddlewareAliases();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerFilamentAssets();
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

        $router->aliasMiddleware('haykal.filament.tenancy', FilamentTenancyMiddleware::class);
    }

    /**
     * Load package views under the `haykal-filament::` namespace so Blade can
     * resolve paths such as `haykal-filament::mapbox.components.mapbox-location-picker`.
     */
    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'haykal-filament');
    }

    /**
     * Load package translations under the `haykal-filament::` namespace
     * so callers can resolve keys such as `haykal-filament::copyable.tooltip`.
     */
    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'haykal-filament');
    }

    /**
     * Register the Mapbox Alpine components + CSS assets with Filament's
     * asset manager. Components are loaded on request (lazy), so pages
     * that do not reference Mapbox pay no cost.
     */
    private function registerFilamentAssets(): void
    {
        FilamentAsset::register([
            // Mapbox runtime CSS.
            Css::make('mapbox', 'https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css')->loadedOnRequest(),
            Css::make('mapbox-draw', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.1/mapbox-gl-draw.css')->loadedOnRequest(),

            // Mapbox Alpine components.
            AlpineComponent::make('mapbox-location-picker', __DIR__.'/../resources/js/mapbox/dist/mapbox-location-picker.js'),
            AlpineComponent::make('mapbox-location-viewer', __DIR__.'/../resources/js/mapbox/dist/mapbox-location-viewer.js'),
            AlpineComponent::make('mapbox-polygons-drawer', __DIR__.'/../resources/js/mapbox/dist/mapbox-polygons-drawer.js'),
            AlpineComponent::make('mapbox-polygons-viewer', __DIR__.'/../resources/js/mapbox/dist/mapbox-polygons-viewer.js'),
        ]);
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/haykal-filament-icons.php' => config_path('haykal-filament-icons.php'),
        ], 'haykal-filament-icons');

        $this->publishes([
            __DIR__.'/../config/mapbox.php' => config_path('mapbox.php'),
        ], 'haykal-filament-mapbox-config');

        $this->publishes([
            __DIR__.'/../resources/css/base-theme.css' => resource_path('css/haykal/base-theme.css'),
        ], 'haykal-filament-theme');

        $this->publishes([
            __DIR__.'/../stubs/panel-theme.css.stub' => resource_path('stubs/haykal/panel-theme.css.stub'),
        ], 'haykal-filament-stubs');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/haykal-filament'),
        ], 'haykal-filament-translations');
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
