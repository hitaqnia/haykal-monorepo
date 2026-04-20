<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Http;

use HiTaqnia\Haykal\Core\Http\Middlewares\SetUserLocaleMiddleware;
use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SetUserLocaleMiddlewareTest extends CoreTestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_leaves_the_locale_unchanged(): void
    {
        app()->setLocale('en');

        $this->runMiddleware(Request::create('/'));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_authenticated_request_applies_the_users_stored_locale(): void
    {
        app()->setLocale('en');

        $user = User::factory()->create(['locale' => 'ar']);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $this->runMiddleware($request);

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_authenticated_user_with_empty_locale_does_not_change_app_locale(): void
    {
        app()->setLocale('en');

        $user = User::factory()->create(['locale' => '']);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $this->runMiddleware($request);

        $this->assertSame('en', app()->getLocale());
    }

    private function runMiddleware(Request $request): void
    {
        (new SetUserLocaleMiddleware)->handle($request, fn () => new Response);
    }
}
