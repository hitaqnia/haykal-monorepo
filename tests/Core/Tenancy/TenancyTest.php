<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Tenancy;

use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;

final class TenancyTest extends CoreTestCase
{
    public function test_get_tenant_id_returns_null_when_none_is_set(): void
    {
        $this->assertNull(Tenancy::getTenantId());
    }

    public function test_set_and_get_tenant_id_round_trips(): void
    {
        Tenancy::setTenantId('01HX0000000000000000000001');

        $this->assertSame('01HX0000000000000000000001', Tenancy::getTenantId());
    }

    public function test_clear_removes_the_active_tenant(): void
    {
        Tenancy::setTenantId('01HX0000000000000000000001');
        Tenancy::clear();

        $this->assertNull(Tenancy::getTenantId());
    }
}
