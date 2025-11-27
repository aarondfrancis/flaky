<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\Flaky\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FlakyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Event::listen(function (CommandStarting $event): void {
            // When the schedule is starting we add an ENV variable. That
            // variable will get propagated down to all spawned commands
            // via the Symfony Process `getDefaultEnv` method.
            if ($event->command === 'schedule:run') {
                Env::getRepository()->set('IS_SCHEDULED', 1);
            }
        });
    }

    public function boot(): void
    {
        // A workaround for testing due to a change in Laravel 10.4.1
        // https://github.com/laravel/framework/pull/46508
        if ($this->app->runningUnitTests()) {
            $this->app->booted(function (): void {
                app(Kernel::class)->rerouteSymfonyCommandEvents();
            });
        }
    }
}
