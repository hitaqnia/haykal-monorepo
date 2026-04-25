<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api;

use Dedoc\Scramble\Scramble;
use HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension;
use HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class HaykalApiServiceProviderTest extends ApiTestCase
{
    public function test_scramble_extensions_for_validation_and_not_found_are_registered(): void
    {
        $this->assertContains(ValidationExceptionExtension::class, Scramble::$extensions);
        $this->assertContains(NotFoundExceptionExtension::class, Scramble::$extensions);
    }

    public function test_laravel_exception_handler_renders_api_failures_through_the_haykal_envelope(): void
    {
        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        $response = $handler->render(
            Request::create('/api/secured'),
            new AuthenticationException,
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame(0, $payload['success']);
        $this->assertSame(401, $payload['code']);
    }

    public function test_non_api_failures_fall_through_to_the_default_handler(): void
    {
        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        // Use a plain runtime exception so the default handler doesn't need a
        // configured `login` route to render the response. The point is that
        // the Haykal envelope is *not* applied to non-`api/*` requests.
        $response = $handler->render(
            Request::create('/dashboard'),
            new RuntimeException('boom'),
        );

        $this->assertNotInstanceOf(JsonResponse::class, $response);
    }
}
