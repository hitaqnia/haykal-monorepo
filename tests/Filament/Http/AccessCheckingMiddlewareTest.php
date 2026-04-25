<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Http;

use Filament\Facades\Filament;
use Filament\Panel;
use HiTaqnia\Haykal\Filament\Http\Middlewares\AccessCheckingMiddleware;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;
use HiTaqnia\Haykal\Tests\Fixtures\TestHuwiyaUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AccessCheckingMiddlewareTest extends FilamentTestCase
{
    use RefreshDatabase;

    public function test_pass_through_when_the_panel_gate_allows_the_user(): void
    {
        $this->registerCurrentPanel('admin');

        $user = TestHuwiyaUser::factory()->create();
        auth()->setUser($user);

        Gate::define('admin.access', fn () => true);

        $response = $this->runMiddleware();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_aborts_with_403_when_the_panel_gate_denies_the_user(): void
    {
        $this->registerCurrentPanel('admin');

        $user = TestHuwiyaUser::factory()->create();
        auth()->setUser($user);

        Gate::define('admin.access', fn () => false);

        try {
            $this->runMiddleware();
            $this->fail('Expected the middleware to abort with HTTP 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_each_panel_id_resolves_its_own_gate(): void
    {
        $this->registerCurrentPanel('operations');

        $user = TestHuwiyaUser::factory()->create();
        auth()->setUser($user);

        Gate::define('admin.access', fn () => true);
        Gate::define('operations.access', fn () => false);

        try {
            $this->runMiddleware();
            $this->fail('Expected the middleware to abort with HTTP 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    private function registerCurrentPanel(string $id): void
    {
        $panel = Panel::make()->id($id)->path($id);

        Filament::registerPanel($panel);
        Filament::setCurrentPanel($panel);
    }

    private function runMiddleware(): Response
    {
        return (new AccessCheckingMiddleware)
            ->handle(Request::create('/admin'), fn () => new Response);
    }
}
