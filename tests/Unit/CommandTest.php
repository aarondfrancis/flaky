<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Flaky\Tests\Unit;

use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event as SchedulingEvent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

class CommandTest extends Base
{
    /** @test */
    public function varies_on_command()
    {
        Artisan::call('flaky:vary --arg=1 --flag');
        $one = Artisan::output();

        Artisan::call('flaky:vary --arg=1');
        $two = Artisan::output();

        Artisan::call('flaky:vary --arg=2 --flag');
        $three = Artisan::output();

        $this->assertStringStartsWith('flaky::command-flaky:vary-', $one);
        $this->assertStringStartsWith('flaky::command-flaky:vary-', $three);

        // The presence or absence of the flag shouldn't matter
        $this->assertEquals($one, $two);

        // But the arg being different does
        $this->assertNotEquals($one, $three);
    }

    /** @test */
    public function scheduled_only()
    {
        Artisan::call('flaky:scheduled');
        $disabled = trim(Artisan::output());

        $this->assertEquals('true', $disabled);

        // This gets added by our event listener, which is tested elsewhere.
        $_ENV['IS_SCHEDULED'] = '1';

        Artisan::call('flaky:scheduled');
        $disabled = trim(Artisan::output());

        $this->assertEquals('false', $disabled);

        unset($_ENV['IS_SCHEDULED']);
    }

    /** @test */
    public function scheduled_command_gets_modified()
    {
        Event::dispatch(new ScheduledTaskStarting(
            $task = new SchedulingEvent(app(CacheEventMutex::class), 'command')
        ));

        $this->assertEquals('IS_SCHEDULED=1 command', $task->command);
    }
}
