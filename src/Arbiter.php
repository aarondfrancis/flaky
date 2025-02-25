<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Flaky;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Macroable;

class Arbiter
{
    use Macroable;

    public $failuresAllowedForSeconds = 60 * 60 * 24 * 365 * 10;

    public $consecutiveFailuresAllowed = INF;

    public $totalFailuresAllowed = INF;

    public $handleFailuresWith;

    protected $key;

    protected $totalFailures;

    protected $consecutiveFailures;

    protected $deadline;

    protected $cache;

    public function __construct($id)
    {
        $this->key = "flaky::$id";
        $this->cache = Cache::store();

        $stats = $this->cache->get($this->key, []);

        $this->totalFailures = Arr::get($stats, 'total', 0);
        $this->consecutiveFailures = Arr::get($stats, 'consecutive', 0);
        $this->deadline = Arr::get($stats, 'deadline');

        $this->handleFailuresWith = function ($e) {
            throw $e;
        };
    }

    public function handle($exception)
    {
        $this->deadline = $this->deadline ?? $this->freshDeadline();

        if ($exception) {
            $this->totalFailures++;
            $this->consecutiveFailures++;
        }

        $this->updateCachedStats($exception);

        if (!is_null($exception) && $this->outOfBounds()) {
            $this->callHandler($exception);
        }
    }

    public function handleFailures($callback)
    {
        $this->handleFailuresWith = $callback;
    }

    public function outOfBounds()
    {
        return $this->tooManyConsecutiveFailures() || $this->tooManyTotalFailures() || $this->beyondDeadline();
    }

    public function tooManyConsecutiveFailures()
    {
        return $this->consecutiveFailures > $this->consecutiveFailuresAllowed;
    }

    public function tooManyTotalFailures()
    {
        return $this->totalFailures > $this->totalFailuresAllowed;
    }

    public function beyondDeadline()
    {
        return now()->timestamp > $this->deadline;
    }

    protected function callHandler($exception)
    {
        call_user_func($this->handleFailuresWith, $exception);
    }

    protected function freshDeadline()
    {
        return now()->addSeconds($this->failuresAllowedForSeconds)->timestamp;
    }

    protected function updateCachedStats($exception)
    {
        $failed = !is_null($exception);

        $stats = $failed ? [
            // Reset if we passed, otherwise just store the incremented value.
            'consecutive' => $this->tooManyConsecutiveFailures() ? 0 : $this->consecutiveFailures,
            'total' => $this->tooManyTotalFailures() ? 0 : $this->totalFailures,

            // Reset if we passed, otherwise carry the deadline forward.
            'deadline' => $this->beyondDeadline() ? $this->freshDeadline() : $this->deadline,
        ] : [
            // Since this was a successful invocation, reset.
            'consecutive' => 0,
            'deadline' => $this->freshDeadline(),

            // Since this is a cumulative stat, carry forward.
            'total' => $this->totalFailures,
        ];

        $this->cache->put($this->key, $stats, now()->addYear());
    }
}
