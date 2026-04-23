<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Rules;

use Closure;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate that a field is an acceptable Iraqi phone number shape.
 *
 * Delegates the regex to `PhoneNumber::INPUT_REGEX` so this rule and the
 * value object stay in lockstep — changing the pattern in one place.
 */
final class PhoneNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $subject = trim((string) $value);

        if ($subject === '' || preg_match(PhoneNumber::INPUT_REGEX, $subject) !== 1) {
            $fail(__('validation.regex', ['attribute' => $attribute]));
        }
    }
}
