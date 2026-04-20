<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Iraqi phone number in E.164 canonical form.
 *
 * Accepts `+964...`, `00964...`, `0...`, or bare `7xxxxxxxxx` on input and
 * normalizes to `+9647XXXXXXXXX` for storage. Readable formatting is
 * available for display (e.g., `+964 770 123 4567`).
 */
final class PhoneNumber implements Stringable
{
    /**
     * Matches any accepted Iraqi phone number shape on input.
     * Use `DIGITS_REGEX` to extract the national 10-digit body.
     */
    public const INPUT_REGEX = '/^(((?:\+|00)964)|(0)*)7\d{9}$/m';

    private const DIGITS_REGEX = '/7\d{9}/';

    /** E.164 canonical form (always starts with `+964`). */
    private readonly string $e164;

    public function __construct(string $phoneNumber)
    {
        $this->e164 = self::normalizeToE164($phoneNumber);
    }

    /**
     * International format: `+964 770 123 4567` (readable) or `+9647701234567` (compact).
     */
    public function getInternational(bool $withPlus = true, bool $readable = false): string
    {
        $number = $withPlus ? $this->e164 : ltrim($this->e164, '+');

        if (! $readable) {
            return $number;
        }

        // Country code is 4 chars (`+964`) or 3 chars (`964`); the national
        // number is 7XXX XXX XXXX where the first 7 is the operator prefix.
        $countryLength = $withPlus ? 4 : 3;

        return substr($number, 0, $countryLength).' '.
            substr($number, $countryLength, 3).' '.
            substr($number, $countryLength + 3, 3).' '.
            substr($number, $countryLength + 6);
    }

    /**
     * National format: `0770 123 4567` (readable) or `07701234567` (compact).
     */
    public function getNational(bool $readable = false): string
    {
        $number = '0'.substr($this->e164, 4);

        if (! $readable) {
            return $number;
        }

        return substr($number, 0, 4).' '.substr($number, 4, 3).' '.substr($number, 7);
    }

    public function __toString(): string
    {
        return $this->getNational();
    }

    private static function normalizeToE164(string $phoneNumber): string
    {
        $subject = trim($phoneNumber);

        if ($subject === '') {
            throw new InvalidArgumentException('Phone number cannot be empty.');
        }

        if (preg_match(self::INPUT_REGEX, $subject) !== 1) {
            throw new InvalidArgumentException('Invalid phone number format.');
        }

        if (preg_match(self::DIGITS_REGEX, $subject, $matches) === 1) {
            return '+964'.$matches[0];
        }

        throw new InvalidArgumentException('Unable to normalize phone number.');
    }
}
