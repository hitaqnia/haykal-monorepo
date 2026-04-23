<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Tenancy;

use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use HiTaqnia\Haykal\Core\Tenancy\Models\Tenant;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ExampleTenant extends Tenant
{
    protected $table = 'example_tenants';
}

class TenantOwnedItem extends Model
{
    use HasTenant;

    protected $table = 'tenant_owned_items';

    protected $guarded = [];

    public $timestamps = false;

    protected string $tenantModel = ExampleTenant::class;
}

class TenantOwnedItemWithoutModel extends Model
{
    use HasTenant;

    protected $table = 'tenant_owned_items';

    protected $guarded = [];

    public $timestamps = false;
}

final class HasTenantTest extends CoreTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('example_tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_owned_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tenant_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Tenancy::clear();
        parent::tearDown();
    }

    public function test_creating_a_row_auto_fills_tenant_id_from_the_active_tenant(): void
    {
        $tenant = ExampleTenant::create(['name' => 'Acme']);
        Tenancy::setTenantId($tenant->getKey());

        $item = TenantOwnedItem::create(['name' => 'Widget']);

        $this->assertSame($tenant->getKey(), $item->tenant_id);
    }

    public function test_explicit_tenant_id_is_not_overwritten(): void
    {
        $owner = ExampleTenant::create(['name' => 'Owner']);
        $other = ExampleTenant::create(['name' => 'Other']);

        Tenancy::setTenantId($other->getKey());

        $item = TenantOwnedItem::create([
            'name' => 'Widget',
            'tenant_id' => $owner->getKey(),
        ]);

        $this->assertSame($owner->getKey(), $item->tenant_id);
    }

    public function test_queries_are_scoped_to_the_active_tenant(): void
    {
        $tenantA = ExampleTenant::create(['name' => 'A']);
        $tenantB = ExampleTenant::create(['name' => 'B']);

        TenantOwnedItem::insert([
            ['name' => 'A-item', 'tenant_id' => $tenantA->getKey()],
            ['name' => 'B-item', 'tenant_id' => $tenantB->getKey()],
        ]);

        Tenancy::setTenantId($tenantA->getKey());

        $names = TenantOwnedItem::pluck('name')->all();

        $this->assertSame(['A-item'], $names);
    }

    public function test_tenant_relation_resolves_to_the_configured_model(): void
    {
        $tenant = ExampleTenant::create(['name' => 'Acme']);
        Tenancy::setTenantId($tenant->getKey());

        $item = TenantOwnedItem::create(['name' => 'Widget']);

        $resolved = $item->tenant;

        $this->assertInstanceOf(ExampleTenant::class, $resolved);
        $this->assertTrue($resolved->is($tenant));
    }

    public function test_tenant_relation_throws_a_clear_error_when_target_model_is_not_declared(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not declare a tenant model/');

        (new TenantOwnedItemWithoutModel)->tenant();
    }
}
