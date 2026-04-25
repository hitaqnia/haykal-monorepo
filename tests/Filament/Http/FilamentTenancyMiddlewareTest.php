<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Http;

use Filament\Facades\Filament;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Filament\Http\Middlewares\FilamentTenancyMiddleware;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class TenantFixture extends Model
{
    use HasUlids;

    protected $table = 'tenant_fixtures';

    protected $guarded = [];

    public $timestamps = false;
}

final class FilamentTenancyMiddlewareTest extends FilamentTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_fixtures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        Tenancy::clear();
        Filament::setTenant(null);

        parent::tearDown();
    }

    public function test_no_active_tenant_leaves_the_haykal_tenancy_context_untouched(): void
    {
        Tenancy::setTenantId('seed-tenant');
        Filament::setTenant(null);

        $this->runMiddleware();

        $this->assertSame('seed-tenant', Tenancy::getTenantId());
    }

    public function test_active_filament_tenant_propagates_into_haykal_tenancy(): void
    {
        $tenant = TenantFixture::create(['name' => 'Acme']);
        Filament::setTenant($tenant, isQuiet: true);

        $this->runMiddleware();

        $this->assertSame((string) $tenant->getKey(), Tenancy::getTenantId());
    }

    private function runMiddleware(): void
    {
        (new FilamentTenancyMiddleware)
            ->handle(Request::create('/admin'), fn () => new Response);
    }
}
