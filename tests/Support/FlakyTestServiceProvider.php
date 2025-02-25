<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Flaky\Tests\Support;

use Hammerstone\Flaky\Arbiter;
use Hammerstone\Flaky\Flaky;
use Hammerstone\Flaky\Tests\Support\Commands\FlakyTestCommand;
use Hammerstone\Flaky\Tests\Support\Commands\FlakyVaryOnInputCommand;
use Hammerstone\Flaky\Tests\Support\Commands\OnlyScheduledCommand;
use Illuminate\Support\ServiceProvider;

class FlakyTestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
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
