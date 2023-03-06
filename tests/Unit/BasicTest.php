<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Flaky\Tests\Unit;

use Carbon\Carbon;
use Exception;
use Hammerstone\Flaky\Flaky;
use Hammerstone\Flaky\Result;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

class BasicTest extends Base
{
    /** @test */
    public function it_works_with_no_exceptions()
    {
        $result = Flaky::make(__FUNCTION__)->run(function () {
            return 1;
        });

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(true, $result->succeeded);
        $this->assertEquals(false, $result->failed);
        $this->assertEquals(1, $result->value);
        $this->assertNull($result->exception);
    }

    /** @test */
    public function failures_past_the_deadline_throw()
    {
        Carbon::setTestNow();

        $flaky = Flaky::make(__FUNCTION__)->allowFailuresForSeconds(60);

        $result = $flaky->run(function () {
            throw new Exception;
        });

        $this->assertTrue($result->failed);

        Carbon::setTestNow(now()->addSeconds(61));

        $this->expectException(Exception::class);

        $flaky->run(function () {
            throw new Exception;
        });
    }

    /** @test */
    public function too_many_consecutive_throw()
    {
        $flaky = Flaky::make(__FUNCTION__)->allowConsecutiveFailures(5);

        for ($i = 1; $i <= 5; $i++) {
            $result = $flaky->run(function () {
                throw new Exception;
            });

            $this->assertTrue($result->failed);
        }

        $this->expectException(Exception::class);

        $flaky->run(function () {
            throw new Exception;
        });
    }

    /** @test */
    public function too_many_total_throw()
    {
        $flaky = Flaky::make(__FUNCTION__)->allowTotalFailures(5);

        for ($i = 1; $i <= 5; $i++) {
            $result = $flaky->run(function () {
                throw new Exception;
            });

            $this->assertTrue($result->failed);
        }

        $this->expectException(Exception::class);

        $flaky->run(function () {
            throw new Exception;
        });
    }

    /** @test */
    public function reported_instead_of_thrown()
    {
        $handler = new class {
            public $reported;

            public function report(Throwable $e)
            {
                $this->reported = $e;
            }
        };

        app()->bind(ExceptionHandler::class, function () use ($handler) {
            return $handler;
        });

        Flaky::make(__FUNCTION__)
            ->allowTotalFailures(0)
            ->reportFailures()
            ->run(function () {
                throw new Exception;
            });

        $this->assertNotNull($handler->reported);
        $this->assertInstanceOf(Exception::class, $handler->reported);
    }

    /** @test */
    public function can_disable()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Oops');

        Flaky::make(__FUNCTION__)
            ->allowFailuresForADay()
            ->disableFlakyProtection()
            ->run(function () {
                throw new Exception('Oops');
            });

        config(['app.env' => 'production']);

    }

    /** @test */
    public function can_disable_locally()
    {
        Flaky::make(__FUNCTION__)
            ->allowFailuresForADay()
            ->run(function () {
                throw new Exception('Oops');
            });

        $this->app->detectEnvironment(function () {
            return 'local';
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Oops');

        Flaky::make(__FUNCTION__)
            ->allowFailuresForADay()
            ->disableLocally()
            ->run(function () {
                throw new Exception('Oops');
            });
    }

    /** @test */
    public function can_handle_failures_ourselves()
    {
        $caught = null;
        $handled = false;

        Flaky::make(__FUNCTION__)
            ->allowConsecutiveFailures(0)
            ->handleFailures(function ($e) use (&$caught, &$handled) {
                $caught = $e;
                $handled = true;
            })
            ->run(function () {
                throw new Exception('Oops');
            });

        $this->assertTrue($handled);
        $this->assertInstanceOf(Exception::class, $caught);
    }

    /** @test */
    public function can_pass_in_our_own_exception()
    {
        $result = Flaky::make(__FUNCTION__)->handle(new Exception('Oops'));

        $this->assertInstanceOf(Result::class, $result);
    }

}
