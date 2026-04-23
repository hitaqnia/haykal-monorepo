<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api;

use Dedoc\Scramble\Scramble;
use HiTaqnia\Haykal\Api\ApiProvider;

/**
 * Fixture provider exposing two versions of the same module.
 */
class VersionedExampleApiProvider extends ApiProvider
{
    protected function name(): string
    {
        return 'widgets-api';
    }

    protected function title(): string
    {
        return 'Widgets API';
    }

    protected function description(): string
    {
        return 'Widgets API — two concurrent versions.';
    }

    protected function versions(): array
    {
        return [
            'v1' => 'api/v1/widgets',
            'v2' => 'api/v2/widgets',
        ];
    }

    protected function version(string $versionId = self::DEFAULT_VERSION): string
    {
        return match ($versionId) {
            'v1' => '1.4.0',
            'v2' => '2.0.0',
            default => '1.0.0',
        };
    }
}

final class VersionedApiProviderTest extends ApiTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            VersionedExampleApiProvider::class,
        ]);
    }

    public function test_each_version_is_registered_as_its_own_scramble_api(): void
    {
        $v1 = Scramble::configure('widgets-api-v1');
        $v2 = Scramble::configure('widgets-api-v2');

        $this->assertSame('api/v1/widgets', $v1->get('api_path'));
        $this->assertSame('api/v2/widgets', $v2->get('api_path'));
    }

    public function test_info_version_is_resolved_per_version_id(): void
    {
        $v1 = Scramble::configure('widgets-api-v1');
        $v2 = Scramble::configure('widgets-api-v2');

        $this->assertSame('1.4.0', $v1->get('info.version'));
        $this->assertSame('2.0.0', $v2->get('info.version'));
    }

    public function test_ui_metadata_is_shared_between_versions(): void
    {
        $v1 = Scramble::configure('widgets-api-v1');
        $v2 = Scramble::configure('widgets-api-v2');

        $this->assertSame('Widgets API', $v1->get('ui.title'));
        $this->assertSame('Widgets API', $v2->get('ui.title'));
    }

    public function test_docs_routes_are_registered_per_version(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->all();

        $this->assertContains('docs/widgets-api-v1', $routes);
        $this->assertContains('docs/widgets-api-v1.json', $routes);
        $this->assertContains('docs/widgets-api-v2', $routes);
        $this->assertContains('docs/widgets-api-v2.json', $routes);
    }
}
