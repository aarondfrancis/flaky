<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Flaky\Providers;

use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FlakyServiceProvider extends ServiceProvider
{
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__ . '/../../config/flaky.php', 'flaky');

        Event::listen(function (ScheduledTaskStarting $event) {
            if ($event->task->command) {
                // Laravel provides no way to tell if a command is running via the
                // scheduler, so we just add our own environment variable here.
                $event->task->command = 'IS_SCHEDULED=1 ' . $event->task->command;
            }
        });
    }

    public function boot()
    {
        //
    }
}
