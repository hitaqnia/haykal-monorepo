<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Casts;

use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast a `phone` column between `PhoneNumber` and its E.164 string form.
 *
 * @implements CastsAttributes<PhoneNumber|null, PhoneNumber|string|null>
 */
final class PhoneNumberCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new PhoneNumber((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $phone = $value instanceof PhoneNumber ? $value : new PhoneNumber((string) $value);

        return $phone->getInternational();
    }
}
