<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use AaronFrancis\Flaky\Result;
use Exception;
use PHPUnit\Framework\Attributes\Test;

class ResultTest extends Base
{
    #[Test]
    public function throw_rethrows_exception_when_present(): void
    {
        $exception = new Exception('Test exception');
        $result = new Result(null, $exception);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $result->throw();
    }

    #[Test]
    public function throw_returns_self_when_no_exception(): void
    {
        $result = new Result('value', null);

        $returned = $result->throw();

        $this->assertSame($result, $returned);
    }

    #[Test]
    public function properties_are_set_correctly_on_success(): void
    {
        $result = new Result('test-value', null);

        $this->assertEquals('test-value', $result->value);
        $this->assertTrue($result->succeeded);
        $this->assertFalse($result->failed);
        $this->assertNull($result->exception);
    }

    #[Test]
    public function properties_are_set_correctly_on_failure(): void
    {
        $exception = new Exception('Failed');
        $result = new Result(null, $exception);

        $this->assertNull($result->value);
        $this->assertFalse($result->succeeded);
        $this->assertTrue($result->failed);
        $this->assertSame($exception, $result->exception);
    }
}
