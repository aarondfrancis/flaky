<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Flaky\Tests\Support\Commands;

use AaronFrancis\Flaky\FlakyCommand;
use Exception;
use Illuminate\Console\Command;

class FlakyTestCommand extends Command
{
    protected $signature = 'flaky {--arg=} {--flag}';

    public function handle()
    {
        FlakyCommand::make($this)
            ->varyOnInput(['arg'])
            ->run([$this, 'process']);
    }

    public function process()
    {
        throw new Exception('oops');
    }
}
