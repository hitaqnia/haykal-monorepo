<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api;

use Dedoc\Scramble\Scramble;
use HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension;
use HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension;
use Illuminate\Support\ServiceProvider;

/**
 * Package service provider for haykal-api.
 *
 * Contributes two globally-applicable Scramble exception-to-response
 * extensions (validation / 404) that render the Haykal envelope, and
 * publishes the Identity routes stub. Per-API document configuration
 * (security schemes, titles, docs UI) lives in ApiProvider subclasses
 * registered by the consuming application.
 */
final class HaykalApiServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Scramble::registerExtensions([
            ValidationExceptionExtension::class,
            NotFoundExceptionExtension::class,
        ]);

        $this->publishes([
            __DIR__.'/../routes/identity-api.stub.php' => base_path('routes/api/identity-api.php'),
        ], 'haykal-api-routes');
    }
}
