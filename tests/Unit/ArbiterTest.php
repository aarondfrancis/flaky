<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Flaky\Tests\Unit;

use Exception;
use Hammerstone\Flaky\Arbiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ArbiterTest extends Base
{
    protected function tearDown(): void
    {
        Cache::forget('flaky::test');

        parent::tearDown();
    }

    protected function setCache($total, $consecutive, $deadline)
    {
        // @TODO once cache is configurable, will need to reflect that here.
        Cache::put('flaky::test', [
            'total' => $total,
            'consecutive' => $consecutive,
            'deadline' => $deadline,
        ], 60);
    }

    /** @test */
    public function it_should_throw_because_of_total()
    {
        $this->setCache(5, 0, now()->addMinute()->timestamp);
        $a = new Arbiter('test');
        $a->totalFailuresAllowed = 5;

        $e = null;
        try {
            $a->handle(new Exception);
        } catch (Throwable $e) {
            //
        }

        $stats = Cache::get('flaky::test');

        // Total should be reset after throwing
        $this->assertEquals(0, $stats['total']);
        $this->assertNotNull($e);
    }

    /** @test */
    public function it_should_throw_because_of_consecutive()
    {
        $this->setCache(0, 5, now()->addMinute()->timestamp);
        $a = new Arbiter('test');
        $a->consecutiveFailuresAllowed = 5;

        $e = null;
        try {
            $a->handle(new Exception);
        } catch (Throwable $e) {
            //
        }

        $stats = Cache::get('flaky::test');

        // Consecutive failures should be reset after throwing
        $this->assertEquals(0, $stats['consecutive']);
        $this->assertNotNull($e);
    }

    /** @test */
    public function it_should_throw_because_of_deadline()
    {
        Carbon::setTestNow();

        // Let the arbiter set the deadline
        $a = new Arbiter('test');

        // Then advance to one second before the deadline
        Carbon::setTestNow(now()->addSeconds($a->failuresAllowedForSeconds - 1));

        // This should not throw an exception
        $a->handle(new Exception);

        // Create a new arbiter
        $a = new Arbiter('test');

        // Handle a successful invocation
        $a->handle(null);

        // Now advance two seconds, which puts us one second past the original deadline.
        Carbon::setTestNow(now()->addSeconds(2));

        // Create a new arbiter
        $a = new Arbiter('test');
        // This should *not* throw, because the successful invocation should've pushed the deadline.
        $a->handle(new Exception);

        // Set the deadline
        $a = new Arbiter('test');
        // Now, fast forward past the deadline
        Carbon::setTestNow(now()->addSeconds($a->failuresAllowedForSeconds + 1));

        $this->expectException(Exception::class);
        // This should throw
        $a->handle(new Exception);
    }

    /** @test */
    public function an_allowed_failure_should_increment_both_and_carry_deadline()
    {
        $deadline = now()->addMinute()->timestamp;

        $this->setCache(0, 0, $deadline);
        $a = new Arbiter('test');
        $a->handle(new Exception);

        $stats = Cache::get('flaky::test');

        $this->assertEquals(1, $stats['consecutive']);
        $this->assertEquals(1, $stats['total']);

        $this->assertEquals($deadline, $stats['deadline']);
    }

    /** @test */
    public function a_success_should_update_cache()
    {
        $this->setCache(2, 2, now()->timestamp);
        $a = new Arbiter('test');
        $deadline = now()->addSeconds($a->failuresAllowedForSeconds)->timestamp;

        Carbon::setTestNow(now()->addMinute());

        $a->handle(null);

        $stats = Cache::get('flaky::test');

        // Total should remain
        $this->assertEquals(2, $stats['total']);
        // Consecutive should be reset
        $this->assertEquals(0, $stats['consecutive']);

        // Deadline should be pushed
        $this->assertEquals($deadline + 60, $stats['deadline']);
    }
}
