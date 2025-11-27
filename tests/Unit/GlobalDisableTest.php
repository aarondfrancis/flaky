<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use AaronFrancis\Flaky\Flaky;
use Exception;
use PHPUnit\Framework\Attributes\Test;

class GlobalDisableTest extends Base
{
    protected function tearDown(): void
    {
        Flaky::globallyEnable();

        parent::tearDown();
    }

    #[Test]
    public function globally_disable_bypasses_all_protection(): void
    {
        Flaky::globallyDisable();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Should throw immediately');

        Flaky::make(__FUNCTION__)
            ->allowFailuresForADay()
            ->run(function () {
                throw new Exception('Should throw immediately');
            });
    }

    #[Test]
    public function globally_enable_restores_protection(): void
    {
        Flaky::globallyDisable();
        Flaky::globallyEnable();

        $result = Flaky::make(__FUNCTION__)
            ->allowFailuresForADay()
            ->run(function () {
                throw new Exception('Should be caught');
            });

        $this->assertTrue($result->failed);
        $this->assertInstanceOf(Exception::class, $result->exception);
    }

    #[Test]
    public function throw_failures_handler_throws_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Thrown by handler');

        Flaky::make(__FUNCTION__)
            ->allowConsecutiveFailures(0)
            ->throwFailures()
            ->run(function () {
                throw new Exception('Thrown by handler');
            });
    }
}
