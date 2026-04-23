<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Identity;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

final class UserFactoryTest extends CoreTestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_persisted_user_with_all_synced_columns_populated(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->exists);
        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertTrue(Str::startsWith($user->phone?->getInternational() ?? '', '+964'));
    }

    public function test_factory_generates_a_huwiya_id_ulid_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->huwiya_id);
        $this->assertTrue(Str::isUlid($user->huwiya_id));
    }

    public function test_without_huwiya_state_creates_a_user_without_a_huwiya_identifier(): void
    {
        $user = User::factory()->withoutHuwiya()->create();

        $this->assertNull($user->huwiya_id);
    }

    public function test_factory_accepts_overrides(): void
    {
        $user = User::factory()->create([
            'name' => 'Fixed Name',
            'locale' => 'ar',
        ]);

        $this->assertSame('Fixed Name', $user->name);
        $this->assertSame('ar', $user->locale);
    }
}
