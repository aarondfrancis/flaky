<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\Flaky\Tests\Support;

use AaronFrancis\Flaky\Arbiter;
use AaronFrancis\Flaky\Flaky;
use AaronFrancis\Flaky\Tests\Support\Commands\FlakyNoVaryCommand;
use AaronFrancis\Flaky\Tests\Support\Commands\FlakyTestCommand;
use AaronFrancis\Flaky\Tests\Support\Commands\FlakyVaryOnInputCommand;
use AaronFrancis\Flaky\Tests\Support\Commands\OnlyScheduledCommand;
use Illuminate\Support\ServiceProvider;

class FlakyTestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            FlakyNoVaryCommand::class,
            FlakyTestCommand::class,
            FlakyVaryOnInputCommand::class,
            OnlyScheduledCommand::class,
        ]);

        Flaky::macro('getProtected', function ($key) {
            return $this->{$key};
        });

        Arbiter::macro('getProtected', function ($key) {
            return $this->{$key};
        });
    }
}
