<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Identity;

use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    #[DataProvider('validInputs')]
    public function test_normalizes_accepted_inputs_to_e164(string $input, string $expectedE164): void
    {
        $phone = new PhoneNumber($input);

        $this->assertSame($expectedE164, $phone->getInternational());
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function validInputs(): iterable
    {
        yield 'already international with plus' => ['+9647701234567', '+9647701234567'];
        yield 'international with 00 prefix' => ['009647701234567', '+9647701234567'];
        yield 'national with leading zero' => ['07701234567', '+9647701234567'];
        yield 'bare national digits' => ['7701234567', '+9647701234567'];
        yield 'whitespace trimmed' => ['  +9647701234567  ', '+9647701234567'];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_inputs(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhoneNumber($input);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'empty string' => [''];
        yield 'non-Iraqi country code' => ['+15551234567'];
        yield 'too short' => ['770123'];
        yield 'too long' => ['07701234567890'];
        yield 'starts with non-7' => ['06701234567'];
        yield 'contains letters' => ['077abc12345'];
    }

    public function test_international_format_supports_readable_and_no_plus_variants(): void
    {
        $phone = new PhoneNumber('07701234567');

        $this->assertSame('+9647701234567', $phone->getInternational());
        $this->assertSame('9647701234567', $phone->getInternational(withPlus: false));
        $this->assertSame('+964 770 123 4567', $phone->getInternational(readable: true));
        $this->assertSame('964 770 123 4567', $phone->getInternational(withPlus: false, readable: true));
    }

    public function test_national_format_supports_compact_and_readable_variants(): void
    {
        $phone = new PhoneNumber('+9647701234567');

        $this->assertSame('07701234567', $phone->getNational());
        $this->assertSame('0770 123 4567', $phone->getNational(readable: true));
    }

    public function test_string_cast_returns_national_compact_form(): void
    {
        $phone = new PhoneNumber('+9647701234567');

        $this->assertSame('07701234567', (string) $phone);
    }
}
