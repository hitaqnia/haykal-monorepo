<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api\Response;

use HiTaqnia\Haykal\Api\Response\ApiResponse;
use HiTaqnia\Haykal\Core\ResultPattern\Error;
use HiTaqnia\Haykal\Tests\Api\ApiTestCase;
use Illuminate\Support\MessageBag;

final class ApiResponseTest extends ApiTestCase
{
    public function test_ok_returns_the_standard_success_envelope(): void
    {
        $response = ApiResponse::ok(message: 'Fetched.', data: ['id' => 1]);

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $payload['success']);
        $this->assertSame(200, $payload['code']);
        $this->assertSame('Fetched.', $payload['message']);
        $this->assertSame(['id' => 1], $payload['data']);
        $this->assertNull($payload['errors']);
    }

    public function test_created_uses_status_201(): void
    {
        $response = ApiResponse::created(data: ['id' => 1]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(201, $response->getData(true)['code']);
    }

    public function test_no_content_returns_status_204_with_null_data(): void
    {
        $response = ApiResponse::noContent();

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_not_found_returns_error_envelope_with_status_404(): void
    {
        $response = ApiResponse::notFound();

        $payload = $response->getData(true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(0, $payload['success']);
        $this->assertSame(404, $payload['code']);
        $this->assertNull($payload['data']);
    }

    public function test_validation_error_accepts_message_bag_and_flattens_to_array(): void
    {
        $bag = new MessageBag(['email' => ['The email is required.']]);

        $response = ApiResponse::validationError(errors: $bag);

        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['email' => ['The email is required.']], $payload['errors']);
    }

    public function test_validation_error_accepts_plain_array_errors(): void
    {
        $response = ApiResponse::validationError(errors: ['name' => ['Required.']]);

        $this->assertSame(
            ['name' => ['Required.']],
            $response->getData(true)['errors'],
        );
    }

    public function test_server_error_omits_debug_block_when_debug_is_disabled(): void
    {
        config()->set('app.debug', false);

        $response = ApiResponse::serverError(debug: ['file' => 'somewhere']);

        $this->assertArrayNotHasKey('debug', $response->getData(true));
    }

    public function test_server_error_includes_debug_block_when_debug_is_enabled(): void
    {
        config()->set('app.debug', true);

        $response = ApiResponse::serverError(debug: ['file' => 'somewhere']);

        $this->assertSame(['file' => 'somewhere'], $response->getData(true)['debug']);
    }

    public function test_business_error_surfaces_domain_error_code_and_message(): void
    {
        $response = ApiResponse::businessError(
            Error::make(code: 4001, message: 'Booking overlaps an existing reservation.'),
        );

        $payload = $response->getData(true);

        // Business error codes above 999 are mapped to HTTP 409 so the
        // transport layer stays HTTP-valid while the domain code reaches
        // the client through the envelope.
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(4001, $payload['code']);
        $this->assertSame('Booking overlaps an existing reservation.', $payload['message']);
    }
}
