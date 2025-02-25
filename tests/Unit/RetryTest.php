<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use AaronFrancis\Flaky\Flaky;
use AaronFrancis\Flaky\Tests\Support\TimeoutException;
use Exception;

class RetryTest extends Base
{
    /** @test */
    public function it_will_retry()
    {
        $timesRun = 0;

        $result = Flaky::make(__FUNCTION__)
            ->retry(10)
            ->run(function ($attempt) use (&$timesRun) {
                $timesRun++;

                if ($attempt < 10) {
                    throw new Exception('Test');
                }

                return 1;
            });

        $this->assertEquals(10, $timesRun);
        $this->assertEquals(false, $result->failed);
        $this->assertEquals(1, $result->value);
    }

    /** @test */
    public function it_retries_a_particular_exception_as_single()
    {
        $timesRun = 0;

        $flaky = Flaky::make(__FUNCTION__)
            ->retry(5, 0, TimeoutException::class)
            ->allowConsecutiveFailures(10);

        $flaky->run(function () use (&$timesRun) {
            $timesRun++;

            throw new TimeoutException;
        });

        // Should retry that exception 5 times.
        $this->assertEquals(5, $timesRun);

        $flaky->run(function () use (&$timesRun) {
            $timesRun++;

            throw new Exception;
        });

        // But a base exception only once.
        $this->assertEquals(6, $timesRun);
    }

    /** @test */
    public function it_retries_a_particular_exception_as_array()
    {
        $timesRun = 0;

        $flaky = Flaky::make(__FUNCTION__)
            ->retry(5, 0, [TimeoutException::class])
            ->allowConsecutiveFailures(10);

        $flaky->run(function () use (&$timesRun) {
            $timesRun++;

            throw new TimeoutException;
        });

        // Should retry that exception 5 times.
        $this->assertEquals(5, $timesRun);

        $flaky->run(function () use (&$timesRun) {
            $timesRun++;

            throw new Exception;
        });

        // But a base exception only once.
        $this->assertEquals(6, $timesRun);
    }
}
