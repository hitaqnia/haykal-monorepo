<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api\Response;

use HiTaqnia\Haykal\Api\Response\ApiExceptionHandler;
use HiTaqnia\Haykal\Tests\Api\ApiTestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApiExceptionHandlerTest extends ApiTestCase
{
    public function test_non_api_requests_fall_through_to_the_default_handler(): void
    {
        $response = ApiExceptionHandler::handle(
            new RuntimeException('boom'),
            Request::create('/dashboard'),
        );

        $this->assertNull($response);
    }

    public function test_validation_exception_becomes_a_422_envelope_carrying_the_error_bag(): void
    {
        $validator = validator(
            ['email' => null],
            ['email' => ['required']],
        );

        $exception = new ValidationException($validator);

        $response = ApiExceptionHandler::handle($exception, Request::create('/api/users'));

        $this->assertNotNull($response);
        $this->assertSame(422, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame(0, $payload['success']);
        $this->assertSame(422, $payload['code']);
        $this->assertArrayHasKey('email', $payload['errors']);
    }

    public function test_authentication_exception_becomes_a_401_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new AuthenticationException,
            Request::create('/api/profile'),
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(401, $response->getData(true)['code']);
    }

    public function test_authorization_exception_becomes_a_403_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new AuthorizationException,
            Request::create('/api/admin'),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_access_denied_http_exception_becomes_a_403_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new AccessDeniedHttpException,
            Request::create('/api/admin'),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_generic_403_http_exception_becomes_a_403_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new HttpException(403, 'Nope.'),
            Request::create('/api/admin'),
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_model_not_found_becomes_a_404_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new ModelNotFoundException,
            Request::create('/api/properties/999'),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(404, $response->getData(true)['code']);
    }

    public function test_route_not_found_becomes_a_404_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new NotFoundHttpException,
            Request::create('/api/missing'),
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_method_not_allowed_becomes_a_405_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new MethodNotAllowedHttpException(['GET']),
            Request::create('/api/properties'),
        );

        $this->assertSame(405, $response->getStatusCode());
    }

    public function test_throttle_exception_becomes_a_429_envelope(): void
    {
        $response = ApiExceptionHandler::handle(
            new ThrottleRequestsException,
            Request::create('/api/login'),
        );

        $this->assertSame(429, $response->getStatusCode());
    }

    public function test_uncaught_exception_falls_through_to_a_500_envelope_without_debug_when_debug_is_off(): void
    {
        config()->set('app.debug', false);

        $response = ApiExceptionHandler::handle(
            new RuntimeException('something exploded'),
            Request::create('/api/things'),
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertArrayNotHasKey('debug', $response->getData(true));
    }

    public function test_uncaught_exception_includes_a_trace_in_the_debug_block_when_debug_is_on(): void
    {
        config()->set('app.debug', true);

        $response = ApiExceptionHandler::handle(
            new RuntimeException('something exploded'),
            Request::create('/api/things'),
        );

        $payload = $response->getData(true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertArrayHasKey('debug', $payload);
        $this->assertArrayHasKey('trace', $payload['debug']);
    }
}
