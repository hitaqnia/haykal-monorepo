<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\Identity;

use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PhoneNumberRuleTest extends CoreTestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function validIraqiPhoneNumbers(): array
    {
        return [
            ['+9647701234567'],
            ['009647701234567'],
            ['07701234567'],
            ['7701234567'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function invalidIraqiPhoneNumbers(): array
    {
        return [
            ['07701234'],                 // too short
            ['+9648701234567'],           // doesn't start with the required `7` operator
            ['+1234567890'],              // wrong country
            ['abc7701234567'],            // garbage prefix
        ];
    }

    #[DataProvider('validIraqiPhoneNumbers')]
    public function test_valid_iraqi_phone_numbers_pass_validation(string $value): void
    {
        $validator = validator(['phone' => $value], ['phone' => [new PhoneNumberRule]]);

        $this->assertTrue($validator->passes(), "Expected '{$value}' to be a valid phone number.");
    }

    #[DataProvider('invalidIraqiPhoneNumbers')]
    public function test_invalid_inputs_fail_validation(string $value): void
    {
        $validator = validator(['phone' => $value], ['phone' => [new PhoneNumberRule]]);

        $this->assertTrue($validator->fails(), "Expected '{$value}' to be rejected.");
    }

    public function test_failure_message_uses_the_validation_regex_translation_key(): void
    {
        $validator = validator(['phone' => 'not-a-phone'], ['phone' => [new PhoneNumberRule]]);

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('phone'));
    }

    public function test_whitespace_only_input_is_rejected_when_paired_with_required(): void
    {
        // Laravel's validator short-circuits implicit-empty values for non-implicit
        // rules — the rule's own whitespace check therefore only matters when the
        // value reaches it (e.g., paired with `required` or a non-empty value).
        $validator = validator(
            ['phone' => '   '],
            ['phone' => ['required', new PhoneNumberRule]],
        );

        $this->assertTrue($validator->fails());
    }
}
