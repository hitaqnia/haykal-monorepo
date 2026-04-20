<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Scramble;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

/**
 * Adds the Huwiya JWT bearer scheme to the generated OpenAPI document.
 *
 * Registered via `Scramble::configure()->withDocumentTransformers(...)`
 * in `HaykalApiServiceProvider`. Every operation in the spec inherits
 * the bearer requirement unless it opts out via Scramble's per-operation
 * configuration.
 */
final class HuwiyaSecurityExtension
{
    public function __invoke(OpenApi $openApi): void
    {
        $openApi->secure(
            SecurityScheme::http('bearer', 'JWT')
                ->setDescription(
                    'Huwiya-issued JWT. Obtain via the Huwiya OAuth2 authorization flow. '.
                    'Send in the `Authorization` header as `Bearer <token>`.',
                ),
        );
    }
}
