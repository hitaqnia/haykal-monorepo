<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Identity;

use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use HiTaqnia\Haykal\Tests\Fixtures\TestHuwiyaUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

final class PhoneNumberCastTest extends CoreTestCase
{
    use RefreshDatabase;

    public function test_get_returns_null_for_a_null_or_empty_database_value(): void
    {
        $cast = new PhoneNumberCast;
        $model = new TestHuwiyaUser;

        $this->assertNull($cast->get($model, 'phone', null, []));
        $this->assertNull($cast->get($model, 'phone', '', []));
    }

    public function test_get_hydrates_the_e164_string_into_a_phone_number_value_object(): void
    {
        $cast = new PhoneNumberCast;
        $model = new TestHuwiyaUser;

        $phone = $cast->get($model, 'phone', '+9647701234567', []);

        $this->assertInstanceOf(PhoneNumber::class, $phone);
        $this->assertSame('+9647701234567', $phone->getInternational());
    }

    public function test_set_returns_null_for_a_null_or_empty_input(): void
    {
        $cast = new PhoneNumberCast;
        $model = new TestHuwiyaUser;

        $this->assertNull($cast->set($model, 'phone', null, []));
        $this->assertNull($cast->set($model, 'phone', '', []));
    }

    public function test_set_normalizes_a_string_input_to_e164_for_storage(): void
    {
        $cast = new PhoneNumberCast;
        $model = new TestHuwiyaUser;

        $stored = $cast->set($model, 'phone', '07701234567', []);

        $this->assertSame('+9647701234567', $stored);
    }

    public function test_set_serializes_a_phone_number_instance_to_e164(): void
    {
        $cast = new PhoneNumberCast;
        $model = new TestHuwiyaUser;

        $stored = $cast->set($model, 'phone', new PhoneNumber('+9647701234567'), []);

        $this->assertSame('+9647701234567', $stored);
    }

    public function test_set_rejects_an_invalid_input_via_the_value_object(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PhoneNumberCast)->set(new TestHuwiyaUser, 'phone', 'not-a-phone', []);
    }

    public function test_round_trip_through_eloquent_persists_and_hydrates_a_phone_number(): void
    {
        $user = TestHuwiyaUser::factory()->create([
            'phone' => '07701234567',
        ]);

        $reloaded = TestHuwiyaUser::query()->findOrFail($user->getKey());

        $this->assertInstanceOf(PhoneNumber::class, $reloaded->phone);
        $this->assertSame('+9647701234567', $reloaded->phone->getInternational());
        $this->assertSame('+9647701234567', $reloaded->getRawOriginal('phone'));
    }
}
