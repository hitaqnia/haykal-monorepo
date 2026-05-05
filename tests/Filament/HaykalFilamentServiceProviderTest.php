<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Router;

final class HaykalFilamentServiceProviderTest extends FilamentTestCase
{
    public function test_middleware_aliases_are_registered(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $aliases = $router->getMiddleware();

        $this->assertArrayHasKey('haykal.filament.tenancy', $aliases);
    }

    public function test_icon_overrides_are_merged_into_filament_icons_config(): void
    {
        $icons = config('filament.icons');

        $this->assertIsArray($icons);
        // Pulled from packages/haykal-filament/config/haykal-filament-icons.php
        $this->assertSame('phosphor-trash-duotone', $icons['actions::delete-action']);
    }

    public function test_publish_theme_command_is_registered(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        $this->assertArrayHasKey('haykal:publish-theme', $kernel->all());
    }
}
