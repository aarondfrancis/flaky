<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use AaronFrancis\Flaky\Flaky;

class SecondsTest extends Base
{
    /** @test */
    public function the_math_is_right()
    {
        $data = [
            60 * 01 * 01 => Flaky::make('a')->allowFailuresForAMinute(),
            60 * 02 * 01 => Flaky::make('a')->allowFailuresForMinutes(2),
            60 * 60 * 01 => Flaky::make('a')->allowFailuresForAnHour(),
            60 * 60 * 02 => Flaky::make('a')->allowFailuresForHours(2),
            60 * 60 * 24 => Flaky::make('a')->allowFailuresForADay(),
            60 * 60 * 48 => Flaky::make('a')->allowFailuresForDays(2),
            02 + (03 * 60) + (04 * 60 * 60) + (05 * 60 * 60 * 24) => Flaky::make('a')->allowFailuresFor(2, 3, 4, 5),
        ];

        foreach ($data as $seconds => $flaky) {
            $this->assertEquals($seconds, $flaky->getProtected('arbiter')->failuresAllowedForSeconds);
        }
    }
}
