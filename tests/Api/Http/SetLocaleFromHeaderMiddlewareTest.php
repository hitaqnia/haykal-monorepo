<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api\Http;

use HiTaqnia\Haykal\Api\Http\Middlewares\SetLocaleFromHeaderMiddleware;
use HiTaqnia\Haykal\Tests\Api\ApiTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SetLocaleFromHeaderMiddlewareTest extends ApiTestCase
{
    public function test_request_without_the_header_leaves_the_locale_unchanged(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->headers->remove('Accept-Language');

        $this->runMiddleware($request, new SetLocaleFromHeaderMiddleware);

        $this->assertSame('en', app()->getLocale());
    }

    public function test_request_with_accept_language_sets_the_locale(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ar');

        $this->runMiddleware($request, new SetLocaleFromHeaderMiddleware);

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_only_the_first_locale_in_a_quality_list_is_used(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr,en;q=0.8');

        $this->runMiddleware($request, new SetLocaleFromHeaderMiddleware);

        $this->assertSame('fr', app()->getLocale());
    }

    public function test_unsupported_locale_is_ignored_when_an_allow_list_is_given(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr');

        $this->runMiddleware($request, new SetLocaleFromHeaderMiddleware(['en', 'ar']));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_supported_locale_is_applied_when_in_the_allow_list(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ar');

        $this->runMiddleware($request, new SetLocaleFromHeaderMiddleware(['en', 'ar']));

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_custom_header_name_is_honored(): void
    {
        app()->setLocale('en');

        $request = Request::create('/');
        $request->headers->set('X-Locale', 'ar');

        $this->runMiddleware($request, new SetLocaleFromHeaderMiddleware(header: 'X-Locale'));

        $this->assertSame('ar', app()->getLocale());
    }

    private function runMiddleware(Request $request, SetLocaleFromHeaderMiddleware $middleware): void
    {
        $middleware->handle($request, fn () => new Response);
    }
}
