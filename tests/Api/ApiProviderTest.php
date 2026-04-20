<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use HiTaqnia\Haykal\Api\ApiProvider;
use HiTaqnia\Haykal\Api\Identity\IdentityApiProvider;

class ExampleApiProvider extends ApiProvider
{
    protected function name(): string
    {
        return 'example-api';
    }

    protected function path(): string
    {
        return 'api/example';
    }

    protected function title(): string
    {
        return 'Example API';
    }

    protected function description(): string
    {
        return 'Example API for ApiProvider tests.';
    }

    protected function primaryColor(): ?string
    {
        return '#4432d2';
    }

    /**
     * @return array<string, SecurityScheme>
     */
    protected function additionalSecuritySchemes(): array
    {
        return [
            'profile' => SecurityScheme::apiKey('header', 'X-Profile-Id'),
        ];
    }
}

final class ApiProviderTest extends ApiTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            ExampleApiProvider::class,
        ]);
    }

    public function test_api_is_registered_with_scramble_under_its_declared_name(): void
    {
        $config = Scramble::configure('example-api');

        $this->assertSame('api/example', $config->get('api_path'));
        $this->assertSame('Example API', $config->get('ui.title'));
        $this->assertSame('1.0.0', $config->get('info.version'));
        $this->assertSame('Example API for ApiProvider tests.', $config->get('info.description'));
    }

    public function test_optional_ui_configuration_is_included_when_provided(): void
    {
        $config = Scramble::configure('example-api');

        $this->assertSame('#4432d2', $config->get('ui.primary'));
    }

    public function test_document_transformer_installs_huwiya_bearer_and_any_additional_schemes(): void
    {
        $config = Scramble::configure('example-api');
        $transformers = $this->documentTransformersOf($config);

        $openApi = new OpenApi('3.1.0');

        foreach ($transformers as $transformer) {
            if (is_callable($transformer)) {
                $transformer($openApi);
            }
        }

        $this->assertArrayHasKey('bearer', $openApi->components->securitySchemes);
        $this->assertArrayHasKey('profile', $openApi->components->securitySchemes);
        $this->assertNotEmpty($openApi->security);
    }

    public function test_ui_and_json_routes_are_registered_at_the_derived_docs_path(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->all();

        $this->assertContains('docs/example-api', $routes);
        $this->assertContains('docs/example-api.json', $routes);
    }

    public function test_identity_api_provider_is_registered_when_booted(): void
    {
        // Register the shipped IdentityApiProvider in isolation and confirm
        // it exposes the expected identity-api registration.
        $this->app->register(IdentityApiProvider::class);

        $config = Scramble::configure('identity-api');

        $this->assertSame('api/identity', $config->get('api_path'));
        $this->assertSame('Identity API', $config->get('ui.title'));
    }

    /**
     * Read Scramble's registered DocumentTransformers for the given API.
     *
     * @return array<int, callable|string>
     */
    private function documentTransformersOf($config): array
    {
        return $config->documentTransformers->all();
    }
}
