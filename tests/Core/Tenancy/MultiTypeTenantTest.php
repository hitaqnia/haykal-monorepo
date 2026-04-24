<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Tenancy;

use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

/**
 * Agency tenant — fixture for multi-type tenancy tests.
 */
class Agency extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $table = 'agencies';

    protected $guarded = [];
}

/**
 * Development company tenant — fixture for multi-type tenancy tests.
 */
class DevelopmentCompany extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $table = 'development_companies';

    protected $guarded = [];
}

/**
 * Property owned by an Agency — uses `agency_id` as the tenant FK.
 */
class AgencyProperty extends Model
{
    use HasTenant;

    protected $table = 'agency_properties';

    protected $guarded = [];

    public $timestamps = false;

    protected string $tenantModel = Agency::class;

    protected string $tenantForeignKey = 'agency_id';
}

/**
 * Project owned by a Development Company — uses `developer_id` as the tenant FK.
 */
class DeveloperProject extends Model
{
    use HasTenant;

    protected $table = 'developer_projects';

    protected $guarded = [];

    public $timestamps = false;

    protected string $tenantModel = DevelopmentCompany::class;

    protected string $tenantForeignKey = 'developer_id';
}

final class MultiTypeTenantTest extends CoreTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('agencies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('development_companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agency_properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('agency_id')->nullable();
        });

        Schema::create('developer_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('developer_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Tenancy::clear();
        parent::tearDown();
    }

    public function test_scope_filters_by_the_models_declared_tenant_foreign_key(): void
    {
        $agencyA = Agency::create(['name' => 'Alpha']);
        $agencyB = Agency::create(['name' => 'Bravo']);

        AgencyProperty::insert([
            ['title' => 'A-villa', 'agency_id' => $agencyA->getKey()],
            ['title' => 'B-villa', 'agency_id' => $agencyB->getKey()],
            ['title' => 'Shared-listing', 'agency_id' => null],
        ]);

        Tenancy::setTenantId($agencyA->getKey());

        $titles = AgencyProperty::orderBy('title')->pluck('title')->all();

        $this->assertSame(['A-villa', 'Shared-listing'], $titles);
    }

    public function test_auto_fill_targets_the_models_declared_foreign_key(): void
    {
        $agency = Agency::create(['name' => 'Alpha']);
        Tenancy::setTenantId($agency->getKey());

        $property = AgencyProperty::create(['title' => 'New villa']);

        $this->assertSame($agency->getKey(), $property->agency_id);
    }

    public function test_developer_scope_uses_developer_id_and_ignores_agency_columns(): void
    {
        $devA = DevelopmentCompany::create(['name' => 'Alpha Dev']);
        $devB = DevelopmentCompany::create(['name' => 'Bravo Dev']);

        DeveloperProject::insert([
            ['title' => 'Skyline', 'developer_id' => $devA->getKey()],
            ['title' => 'Harbor', 'developer_id' => $devB->getKey()],
        ]);

        Tenancy::setTenantId($devA->getKey());

        $titles = DeveloperProject::orderBy('title')->pluck('title')->all();

        $this->assertSame(['Skyline'], $titles);
    }

    public function test_tenant_relation_resolves_against_the_declared_tenant_model_and_foreign_key(): void
    {
        $agency = Agency::create(['name' => 'Alpha']);
        Tenancy::setTenantId($agency->getKey());

        $property = AgencyProperty::create(['title' => 'Villa']);

        $this->assertInstanceOf(Agency::class, $property->tenant);
        $this->assertTrue($property->tenant->is($agency));
    }

    public function test_models_without_custom_foreign_key_still_use_the_default_tenant_id_column(): void
    {
        // Defined inline to avoid introducing another fixture table — the
        // point is that the default still resolves to `tenant_id`.
        $instance = new class extends Model
        {
            use HasTenant;

            protected string $tenantModel = Agency::class;
        };

        $this->assertSame('tenant_id', $instance->getTenantForeignKey());
    }
}
