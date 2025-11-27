<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\Flaky\Tests\Support\Commands;

use AaronFrancis\Flaky\FlakyCommand;
use Illuminate\Console\Command;

class FlakyNoVaryCommand extends Command
{
    protected $signature = 'flaky:novary {--arg=}';

    public function handle()
    {
        $flaky = FlakyCommand::make($this)->instance();

        // Output the key for testing
        $this->line($flaky->getProtected('arbiter')->getProtected('key'));
    }
}
