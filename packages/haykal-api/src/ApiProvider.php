<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

/**
 * Base class for every API module in a Haykal application.
 *
 * A Haykal app typically composes several independent APIs (identity,
 * management, residents, …). Each lives under its own URL prefix, has
 * its own Scramble-generated OpenAPI document, and its own docs UI.
 *
 * Subclass this provider for every new API module, declare the four
 * required identity hooks — `name`, `path`, `title`, `description` —
 * and optionally override the presentation hooks (`version`, `logo`,
 * `primaryColor`, `docsPath`) or contribute extra security schemes
 * via `additionalSecuritySchemes()`.
 *
 * Registration handles:
 *   1. `Scramble::registerApi()` with the module's identity.
 *   2. A document transformer that installs the Huwiya bearer scheme
 *      as the default security requirement and appends any
 *      module-specific schemes (e.g., tenant or profile headers).
 *   3. The UI route (`docs/<name>`) and JSON spec route.
 *
 * Route files are intentionally not managed here — they live under
 * `routes/api/<module>-api.php` in the application and are included
 * from `routes/api.php` as usual.
 */
abstract class ApiProvider extends ServiceProvider
{
    /**
     * The Scramble API identifier. Drives the registration key, the UI
     * route, and the JSON spec route. Should be kebab-case and unique
     * across the application (e.g., `identity-api`, `residents-api`).
     */
    abstract protected function name(): string;

    /**
     * URL prefix for the API (e.g., `api/identity`). Scramble uses this
     * to discover which routes belong to the module.
     */
    abstract protected function path(): string;

    /**
     * Human-readable title shown in the Scramble UI and the OpenAPI spec.
     */
    abstract protected function title(): string;

    /**
     * Short description of the API shown in the generated OpenAPI spec.
     */
    abstract protected function description(): string;

    /**
     * API version string shown in the OpenAPI spec. Override when bumping.
     */
    protected function version(): string
    {
        return '1.0.0';
    }

    /**
     * Optional logo URL shown in the Scramble UI.
     */
    protected function logo(): ?string
    {
        return null;
    }

    /**
     * Optional primary brand color for the Scramble UI, in any CSS-valid form.
     */
    protected function primaryColor(): ?string
    {
        return null;
    }

    /**
     * URL path for the docs UI. Defaults to `docs/<name>`. The JSON spec
     * is served at `<docsPath>.json`.
     */
    protected function docsPath(): string
    {
        return 'docs/'.$this->name();
    }

    /**
     * Extra security schemes to register on this API in addition to the
     * always-present Huwiya bearer scheme.
     *
     * Typical additions are tenant or profile header schemes. Override
     * and return an associative array keyed by scheme name:
     *
     *     return [
     *         'complex' => SecurityScheme::apiKey('header', 'X-Complex-Id'),
     *         'profile' => SecurityScheme::apiKey('header', 'X-Profile-Id'),
     *     ];
     *
     * @return array<string, SecurityScheme>
     */
    protected function additionalSecuritySchemes(): array
    {
        return [];
    }

    final public function boot(): void
    {
        Scramble::registerApi($this->name(), [
            'api_path' => $this->path(),
            'info' => [
                'version' => $this->version(),
                'description' => $this->description(),
            ],
            'ui' => $this->buildUiConfig(),
        ])->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->components->securitySchemes['bearer'] = SecurityScheme::http('bearer', 'JWT')
                ->setDescription(
                    'Huwiya-issued JWT bearer token. Obtain via the Huwiya OAuth2 authorization flow '.
                    'and send in the `Authorization` header as `Bearer <token>`.',
                );

            foreach ($this->additionalSecuritySchemes() as $schemeName => $scheme) {
                $openApi->components->securitySchemes[$schemeName] = $scheme;
            }

            $openApi->security[] = new SecurityRequirement(['bearer' => []]);
        });

        Scramble::registerUiRoute(path: $this->docsPath(), api: $this->name());
        Scramble::registerJsonSpecificationRoute(path: $this->docsPath().'.json', api: $this->name());
    }

    /**
     * @return array<string, string>
     */
    private function buildUiConfig(): array
    {
        $ui = ['title' => $this->title()];

        if ($this->logo() !== null) {
            $ui['logo'] = $this->logo();
        }

        if ($this->primaryColor() !== null) {
            $ui['primary'] = $this->primaryColor();
        }

        return $ui;
    }
}
