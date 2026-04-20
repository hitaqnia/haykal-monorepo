<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api;

use Dedoc\Scramble\Scramble;
use HiTaqnia\Haykal\Api\Scramble\HuwiyaSecurityExtension;
use HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension;
use HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension;
use Illuminate\Support\ServiceProvider;

final class HaykalApiServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerScrambleIntegrations();
        $this->registerRoutesStub();
    }

    /**
     * Register the Scramble pieces haykal-api contributes:
     *
     *   1. Two exception-to-response extensions that render the Haykal envelope
     *      for validation failures and 404s.
     *   2. A document transformer that adds the Huwiya bearer security scheme
     *      to the generated OpenAPI document.
     */
    private function registerScrambleIntegrations(): void
    {
        Scramble::registerExtensions([
            ValidationExceptionExtension::class,
            NotFoundExceptionExtension::class,
        ]);

        Scramble::configure()->withDocumentTransformers(new HuwiyaSecurityExtension);
    }

    /**
     * Publish the Identity API routes stub so consuming apps can include it
     * from `routes/api.php` (or mount it directly in `bootstrap/app.php`).
     */
    private function registerRoutesStub(): void
    {
        $this->publishes([
            __DIR__.'/../routes/identity-api.stub.php' => base_path('routes/api/identity-api.php'),
        ], 'haykal-api-routes');
    }
}
