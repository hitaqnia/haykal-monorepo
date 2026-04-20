<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Tenancy;

use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Core\Tenancy\TenantScope;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

#[ScopedBy(TenantScope::class)]
class TenantScopedItem extends Model
{
    protected $table = 'tenant_scoped_items';

    protected $guarded = [];

    public $timestamps = false;
}

final class TenantScopeTest extends CoreTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_scoped_items', function (Blueprint $table) {
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

    public function test_without_active_tenant_all_rows_are_visible(): void
    {
        TenantScopedItem::insert([
            ['name' => 'Alpha', 'tenant_id' => 'tenant-a'],
            ['name' => 'Beta', 'tenant_id' => 'tenant-b'],
            ['name' => 'Shared', 'tenant_id' => null],
        ]);

        $this->assertSame(3, TenantScopedItem::count());
    }

    public function test_with_active_tenant_only_matching_and_shared_rows_are_visible(): void
    {
        TenantScopedItem::insert([
            ['name' => 'Alpha', 'tenant_id' => 'tenant-a'],
            ['name' => 'Beta', 'tenant_id' => 'tenant-b'],
            ['name' => 'Shared', 'tenant_id' => null],
        ]);

        Tenancy::setTenantId('tenant-a');

        $names = TenantScopedItem::orderBy('name')->pluck('name')->all();

        $this->assertSame(['Alpha', 'Shared'], $names);
    }
}
