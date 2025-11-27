<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use AaronFrancis\Flaky\Flaky;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class CombinedThresholdsTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache for each test
        Cache::flush();
    }

    #[Test]
    public function consecutive_resets_on_success_but_total_persists(): void
    {
        $id = __FUNCTION__;

        // Fail twice
        for ($i = 0; $i < 2; $i++) {
            $result = Flaky::make($id)
                ->allowConsecutiveFailures(3)
                ->allowTotalFailures(10)
                ->run(fn() => throw new Exception);
            $this->assertTrue($result->failed);
        }

        // Success resets consecutive
        $result = Flaky::make($id)
            ->allowConsecutiveFailures(3)
            ->allowTotalFailures(10)
            ->run(fn() => 'success');
        $this->assertTrue($result->succeeded);

        // Can fail 3 more times (consecutive reset)
        for ($i = 0; $i < 3; $i++) {
            $result = Flaky::make($id)
                ->allowConsecutiveFailures(3)
                ->allowTotalFailures(10)
                ->run(fn() => throw new Exception);
            $this->assertTrue($result->failed);
        }

        // 4th consecutive should throw
        $this->expectException(Exception::class);
        Flaky::make($id)
            ->allowConsecutiveFailures(3)
            ->allowTotalFailures(10)
            ->run(fn() => throw new Exception);
    }

    #[Test]
    public function total_failures_accumulate_across_successes(): void
    {
        $id = __FUNCTION__;

        // Fail 2, succeed, fail 3 = 5 total
        for ($i = 0; $i < 2; $i++) {
            Flaky::make($id)
                ->allowConsecutiveFailures(100)
                ->allowTotalFailures(5)
                ->run(fn() => throw new Exception);
        }
        Flaky::make($id)
            ->allowConsecutiveFailures(100)
            ->allowTotalFailures(5)
            ->run(fn() => 'success');
        for ($i = 0; $i < 3; $i++) {
            Flaky::make($id)
                ->allowConsecutiveFailures(100)
                ->allowTotalFailures(5)
                ->run(fn() => throw new Exception);
        }

        // 6th total failure should throw
        $this->expectException(Exception::class);
        Flaky::make($id)
            ->allowConsecutiveFailures(100)
            ->allowTotalFailures(5)
            ->run(fn() => throw new Exception);
    }

    #[Test]
    public function deadline_and_consecutive_work_together(): void
    {
        Carbon::setTestNow();
        $id = __FUNCTION__;

        // 2 failures allowed
        Flaky::make($id)
            ->allowFailuresForSeconds(60)
            ->allowConsecutiveFailures(2)
            ->run(fn() => throw new Exception);
        Flaky::make($id)
            ->allowFailuresForSeconds(60)
            ->allowConsecutiveFailures(2)
            ->run(fn() => throw new Exception);

        // 3rd consecutive throws even within deadline
        $this->expectException(Exception::class);
        Flaky::make($id)
            ->allowFailuresForSeconds(60)
            ->allowConsecutiveFailures(2)
            ->run(fn() => throw new Exception);
    }
}
