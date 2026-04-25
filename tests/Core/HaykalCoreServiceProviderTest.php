<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core;

use HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware;
use Illuminate\Routing\Router;

final class HaykalCoreServiceProviderTest extends CoreTestCase
{
    public function test_permissions_team_middleware_is_registered_under_the_short_alias(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $aliases = $router->getMiddleware();

        $this->assertArrayHasKey('haykal.permissions.team', $aliases);
        $this->assertSame(PermissionsTeamMiddleware::class, $aliases['haykal.permissions.team']);
    }
}
