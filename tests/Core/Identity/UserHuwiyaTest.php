<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Identity;

use HiTaqnia\Haykal\Core\Identity\Models\Role;
use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Huwiya\TokenClaims;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class UserHuwiyaTest extends CoreTestCase
{
    use RefreshDatabase;

    public function test_find_or_create_from_huwiya_creates_a_new_user_with_all_claim_fields(): void
    {
        $claims = $this->claims([
            'id' => '01HX0000000000000000000042',
            'name' => 'Ali Al-Saadi',
            'phone' => '+9647701234567',
            'email' => 'ali@example.test',
            'locale' => 'ar',
            'zoneinfo' => 'Asia/Baghdad',
            'theme' => 'dark',
        ]);

        $user = User::findOrCreateFromHuwiya($claims);

        $this->assertTrue($user->exists);
        $this->assertSame('01HX0000000000000000000042', $user->huwiya_id);
        $this->assertSame('Ali Al-Saadi', $user->name);
        $this->assertSame('ali@example.test', $user->email);
        $this->assertSame('ar', $user->locale);
        $this->assertSame('Asia/Baghdad', $user->zoneinfo);
        $this->assertSame('dark', $user->theme);
    }

    public function test_find_or_create_from_huwiya_returns_the_same_user_on_subsequent_logins(): void
    {
        $claims = $this->claims(['id' => '01HX0000000000000000000042']);

        $firstUser = User::findOrCreateFromHuwiya($claims);
        $secondUser = User::findOrCreateFromHuwiya($claims);

        $this->assertSame($firstUser->getKey(), $secondUser->getKey());
        $this->assertSame(1, User::count());
    }

    public function test_update_attributes_from_claims_overwrite_all_synced_fields_on_relogin(): void
    {
        $initial = $this->claims([
            'id' => '01HX0000000000000000000042',
            'name' => 'Old Name',
            'phone' => '+9647701234567',
            'locale' => 'en',
            'zoneinfo' => 'UTC',
            'theme' => 'light',
        ]);

        User::findOrCreateFromHuwiya($initial);

        $updated = $this->claims([
            'id' => '01HX0000000000000000000042',
            'name' => 'New Name',
            'phone' => '+9647701234567',
            'locale' => 'ar',
            'zoneinfo' => 'Asia/Baghdad',
            'theme' => 'dark',
        ]);

        $fresh = User::findOrCreateFromHuwiya($updated)->fresh();

        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('ar', $fresh->locale);
        $this->assertSame('Asia/Baghdad', $fresh->zoneinfo);
        $this->assertSame('dark', $fresh->theme);
    }

    public function test_user_can_be_assigned_a_spatie_role_scoped_to_a_tenant(): void
    {
        $tenantId = '01HX0000000000000000000099';
        setPermissionsTeamId($tenantId);

        $role = Role::create([
            'name' => 'member',
            'guard_name' => 'web',
            'team_id' => $tenantId,
        ]);
        $user = User::findOrCreateFromHuwiya($this->claims(['id' => '01HX0000000000000000000042']));

        $user->assignRole($role);

        $this->assertTrue($user->fresh()->hasRole('member'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function claims(array $overrides = []): TokenClaims
    {
        $defaults = [
            'id' => '01HX0000000000000000000001',
            'name' => 'Test User',
            'phone' => '+9647701234567',
            'email' => null,
            'scopes' => [],
            'iss' => 'https://huwiya.test',
            'aud' => 'test-project',
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return TokenClaims::fromArray(array_merge($defaults, $overrides));
    }
}
