<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Flaky\Tests\Support\Commands;

use Hammerstone\Flaky\FlakyCommand;
use Illuminate\Console\Command;

class FlakyVaryOnInputCommand extends Command
{
    protected $signature = 'flaky:vary {--arg=} {--flag}';

    public function handle()
    {
        $arbiter = FlakyCommand::make($this)->varyOnInput(['arg'])->getProtected('arbiter');

        $this->line($arbiter->getProtected('key'));
    }
}
