<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace AaronFrancis\Flaky\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FlakyServiceProvider extends ServiceProvider
{
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__ . '/../../config/flaky.php', 'flaky');

        Event::listen(function (CommandStarting $event) {
            // When the schedule is starting we add an ENV variable. That
            // variable will get propagated down to all spawned commands
            // via the Symfony Process `getDefaultEnv` method.
            if ($event->command === 'schedule:run') {
                Env::getRepository()->set('IS_SCHEDULED', 1);
            }
        });
    }

    public function boot()
    {
        //
    }
}
