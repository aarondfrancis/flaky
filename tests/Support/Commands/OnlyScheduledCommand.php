<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Flaky\Tests\Support\Commands;

use Hammerstone\Flaky\FlakyCommand;
use Illuminate\Console\Command;

class OnlyScheduledCommand extends Command
{
    protected $signature = 'flaky:scheduled';

    public function handle()
    {
        $disabled = FlakyCommand::make($this)->getProtected('flakyProtectionDisabled');

        $this->line(json_encode($disabled));
    }
}
