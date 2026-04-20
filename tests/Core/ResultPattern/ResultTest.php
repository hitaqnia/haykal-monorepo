<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\ResultPattern;

use HiTaqnia\Haykal\Core\ResultPattern\Error;
use HiTaqnia\Haykal\Core\ResultPattern\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function test_success_carries_data_and_reports_success(): void
    {
        $result = Result::success(['id' => 42]);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertSame(['id' => 42], $result->getData());
        $this->assertNull($result->getError());
    }

    public function test_success_may_carry_null_data(): void
    {
        $result = Result::success();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getData());
    }

    public function test_failure_carries_an_error_and_reports_failure(): void
    {
        $error = Error::make(code: 1001, message: 'Something broke.');
        $result = Result::failure($error);

        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isSuccess());
        $this->assertNull($result->getData());
        $this->assertSame($error, $result->getError());
        $this->assertSame(1001, $result->getError()->getCode());
        $this->assertSame('Something broke.', $result->getError()->getMessage());
    }

    public function test_error_message_is_optional(): void
    {
        $error = Error::make(500);

        $this->assertSame(500, $error->getCode());
        $this->assertNull($error->getMessage());
    }
}
