<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class CommandTest extends Base
{
    #[Test]
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

    #[Test]
    public function scheduled_only()
    {
        Artisan::call('flaky:scheduled');
        $disabled = trim(Artisan::output());

        $this->assertEquals('true', $disabled);

        // This gets added by our event listener, which is tested elsewhere.
        Env::getRepository()->set('IS_SCHEDULED', 1);

        Artisan::call('flaky:scheduled');
        $disabled = trim(Artisan::output());

        $this->assertEquals('false', $disabled);

        Env::getRepository()->set('IS_SCHEDULED', 0);
    }

    #[Test]
    public function env_var_gets_set()
    {
        $repo = Env::getRepository();

        $repo->set('IS_SCHEDULED', 0);

        $this->assertEquals('0', $repo->get('IS_SCHEDULED'));

        Artisan::call('schedule:run');

        $this->assertEquals('1', $repo->get('IS_SCHEDULED'));
    }
}
