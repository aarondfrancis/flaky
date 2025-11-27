<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Flaky;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Macroable;
use Throwable;

class Arbiter
{
    use Macroable;

    public int $failuresAllowedForSeconds = 60 * 60 * 24 * 365 * 10;

    public int|float $consecutiveFailuresAllowed = INF;

    public int|float $totalFailuresAllowed = INF;

    /** @var callable(Throwable): void */
    public $handleFailuresWith;

    protected string $key;

    protected int $totalFailures;

    protected int $consecutiveFailures;

    protected ?int $deadline;

    protected Repository $cache;

    public function __construct(string $id)
    {
        $this->key = "flaky::$id";
        $this->cache = Cache::store();

        /** @var array{total?: int, consecutive?: int, deadline?: int|null} $stats */
        $stats = $this->cache->get($this->key, []);

        $this->totalFailures = Arr::get($stats, 'total', 0);
        $this->consecutiveFailures = Arr::get($stats, 'consecutive', 0);
        $this->deadline = Arr::get($stats, 'deadline');

        $this->handleFailuresWith = function (Throwable $e): never {
            throw $e;
        };
    }

    public function handle(?Throwable $exception): void
    {
        $this->deadline = $this->deadline ?? $this->freshDeadline();

        if ($exception !== null) {
            $this->totalFailures++;
            $this->consecutiveFailures++;
        }

        $this->updateCachedStats($exception);

        if ($exception !== null && $this->outOfBounds()) {
            $this->callHandler($exception);
        }
    }

    public function handleFailures(callable $callback): void
    {
        $this->handleFailuresWith = $callback;
    }

    public function outOfBounds(): bool
    {
        return $this->tooManyConsecutiveFailures() || $this->tooManyTotalFailures() || $this->beyondDeadline();
    }

    public function tooManyConsecutiveFailures(): bool
    {
        return $this->consecutiveFailures > $this->consecutiveFailuresAllowed;
    }

    public function tooManyTotalFailures(): bool
    {
        return $this->totalFailures > $this->totalFailuresAllowed;
    }

    public function beyondDeadline(): bool
    {
        return now()->timestamp > $this->deadline;
    }

    protected function callHandler(Throwable $exception): void
    {
        call_user_func($this->handleFailuresWith, $exception);
    }

    protected function freshDeadline(): int
    {
        return now()->addSeconds($this->failuresAllowedForSeconds)->timestamp;
    }

    protected function updateCachedStats(?Throwable $exception): void
    {
        $failed = $exception !== null;

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
