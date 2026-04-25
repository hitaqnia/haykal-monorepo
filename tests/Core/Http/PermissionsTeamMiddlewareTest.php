<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Http;

use HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use HiTaqnia\Haykal\Tests\Fixtures\TestHuwiyaUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\PermissionRegistrar;

final class PermissionsTeamMiddlewareTest extends CoreTestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Tenancy::clear();

        parent::tearDown();
    }

    public function test_unauthenticated_request_does_not_touch_the_spatie_team_id(): void
    {
        $registrar = $this->app->make(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId('seed-team');

        $this->runMiddleware(Request::create('/'));

        $this->assertSame('seed-team', $registrar->getPermissionsTeamId());
    }

    public function test_authenticated_request_forwards_the_active_tenant_to_spatie(): void
    {
        $user = TestHuwiyaUser::factory()->create();
        auth()->setUser($user);

        Tenancy::setTenantId('01HX0000000000000000000001');

        $this->runMiddleware(Request::create('/'));

        $registrar = $this->app->make(PermissionRegistrar::class);
        $this->assertSame('01HX0000000000000000000001', $registrar->getPermissionsTeamId());
    }

    public function test_authenticated_request_clears_the_team_id_when_no_tenant_is_active(): void
    {
        $registrar = $this->app->make(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId('stale-team');

        $user = TestHuwiyaUser::factory()->create();
        auth()->setUser($user);

        Tenancy::clear();

        $this->runMiddleware(Request::create('/'));

        $this->assertNull($registrar->getPermissionsTeamId());
    }

    public function test_middleware_alias_is_registered_under_the_short_name(): void
    {
        $aliases = $this->app['router']->getMiddleware();

        $this->assertArrayHasKey('haykal.permissions.team', $aliases);
        $this->assertSame(PermissionsTeamMiddleware::class, $aliases['haykal.permissions.team']);
    }

    private function runMiddleware(Request $request): void
    {
        (new PermissionsTeamMiddleware)->handle($request, fn () => new Response);
    }
}
