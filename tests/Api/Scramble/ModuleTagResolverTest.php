<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api\Scramble;

use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use HiTaqnia\Haykal\Api\Scramble\ModuleTagResolver;
use Mockery;
use PHPUnit\Framework\TestCase;

final class ModuleTagResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_extracts_module_name_from_standard_haykal_apis_namespace(): void
    {
        $tags = $this->resolve('App\\Apis\\Properties\\Controllers\\CreatePropertyController');

        $this->assertSame(['Properties'], $tags);
    }

    public function test_humanizes_module_names_written_in_pascal_case(): void
    {
        $tags = $this->resolve('App\\Apis\\PropertyManagement\\Controllers\\ListUnitsController');

        $this->assertSame(['Property Management'], $tags);
    }

    public function test_returns_empty_tags_for_controllers_outside_the_apis_namespace(): void
    {
        $tags = $this->resolve('App\\Http\\Controllers\\HomeController');

        $this->assertSame([], $tags);
    }

    public function test_returns_empty_tags_for_routes_without_a_resolvable_class_name(): void
    {
        $tags = $this->resolve(null);

        $this->assertSame([], $tags);
    }

    private function resolve(?string $className): array
    {
        $routeInfo = Mockery::mock(RouteInfo::class);
        $routeInfo->shouldReceive('className')->andReturn($className);

        $operation = new Operation('get');

        return (new ModuleTagResolver)($routeInfo, $operation);
    }
}
