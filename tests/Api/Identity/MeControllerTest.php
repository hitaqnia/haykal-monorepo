<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api\Identity;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Tests\Api\ApiTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class MeControllerTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->getJson('/api/identity/me');

        $this->assertApiError($response, code: 401);
    }

    public function test_authenticated_request_returns_the_huwiya_user_profile_in_the_envelope(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Al-Saadi',
            'phone' => '+9647701234567',
            'email' => 'ali@example.test',
            'locale' => 'ar',
            'zoneinfo' => 'Asia/Baghdad',
            'theme' => 'dark',
        ]);

        $this->authenticateAs($user);

        $response = $this->getJson('/api/identity/me');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('message', 'Profile retrieved successfully.');
        $response->assertJsonPath('data.id', $user->getKey());
        $response->assertJsonPath('data.name', 'Ali Al-Saadi');
        $response->assertJsonPath('data.phone', '07701234567');
        $response->assertJsonPath('data.email', 'ali@example.test');
        $response->assertJsonPath('data.locale', 'ar');
        $response->assertJsonPath('data.zoneinfo', 'Asia/Baghdad');
        $response->assertJsonPath('data.theme', 'dark');
    }
}
