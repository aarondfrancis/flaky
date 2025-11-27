<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Flaky;

use Illuminate\Support\Traits\Macroable;
use Throwable;

class Flaky
{
    use Macroable;

    protected static bool $disabledGlobally = false;

    protected Arbiter $arbiter;

    /** @var array{times: int, sleep: int, when: callable|null} */
    protected array $retry = [];

    protected bool $flakyProtectionDisabled = false;

    /** @var array<class-string<Throwable>>|null */
    protected ?array $flakyExceptions = null;

    public static function make(string $id): static
    {
        return new static($id);
    }

    public static function globallyDisable(): void
    {
        static::$disabledGlobally = true;
    }

    public static function globallyEnable(): void
    {
        static::$disabledGlobally = false;
    }

    public function __construct(string $id)
    {
        $this->retry();
        $this->arbiter = new Arbiter($id);
    }

    public function run(callable $callable): Result
    {
        $exception = null;
        $value = null;

        try {
            $value = retry($this->retry['times'], $callable, $this->retry['sleep'], $this->retry['when']);
        } catch (Throwable $e) {
            $exception = $e;
        }

        if ($this->shouldThrowImmediately($exception)) {
            throw $exception;
        }

        $this->arbiter->handle($exception);

        return new Result($value, $exception);
    }

    public function handle(?Throwable $exception = null): Result
    {
        return $this->run(function () use ($exception) {
            if ($exception !== null) {
                throw $exception;
            }
        });
    }

    public function disableLocally(): static
    {
        if (app()->environment('local')) {
            $this->disableFlakyProtection();
        }

        return $this;
    }

    public function disableFlakyProtection(bool $disabled = true): static
    {
        $this->flakyProtectionDisabled = $disabled;

        return $this;
    }

    /**
     * @param  string|array<class-string<Throwable>>|callable|null  $when
     */
    public function retry(int $times = 0, int $sleepMilliseconds = 0, string|array|callable|null $when = null): static
    {
        // We just store these for now and then use them in the `run` method.
        $this->retry = [
            'times' => $times,
            'sleep' => $sleepMilliseconds,
            'when' => $this->normalizeRetryWhen($when),
        ];

        return $this;
    }

    public function handleFailures(callable $callback): static
    {
        $this->arbiter->handleFailures($callback);

        return $this;
    }

    public function reportFailures(): static
    {
        return $this->handleFailures(function ($e) {
            report($e);
        });
    }

    public function throwFailures(): static
    {
        return $this->handleFailures(function ($e) {
            throw $e;
        });
    }

    public function allowFailuresFor(int $seconds = 0, int $minutes = 0, int $hours = 0, int $days = 0): static
    {
        return $this->allowFailuresForSeconds(
            $seconds + (60 * $minutes) + (60 * 60 * $hours) + (60 * 60 * 24 * $days)
        );
    }

    public function allowFailuresForSeconds(int $seconds): static
    {
        $this->arbiter->failuresAllowedForSeconds = $seconds;

        return $this;
    }

    public function allowFailuresForAMinute(): static
    {
        return $this->allowFailuresForMinutes(1);
    }

    public function allowFailuresForMinutes(int $minutes): static
    {
        return $this->allowFailuresForSeconds(60 * $minutes);
    }

    public function allowFailuresForAnHour(): static
    {
        return $this->allowFailuresForHours(1);
    }

    public function allowFailuresForHours(int $hours): static
    {
        return $this->allowFailuresForSeconds(60 * 60 * $hours);
    }

    public function allowFailuresForADay(): static
    {
        return $this->allowFailuresForDays(1);
    }

    public function allowFailuresForDays(int $days): static
    {
        return $this->allowFailuresForSeconds(60 * 60 * 24 * $days);
    }

    public function allowConsecutiveFailures(int|float $failures): static
    {
        $this->arbiter->consecutiveFailuresAllowed = $failures;

        return $this;
    }

    public function allowTotalFailures(int|float $failures): static
    {
        $this->arbiter->totalFailuresAllowed = $failures;

        return $this;
    }

    /**
     * @param  array<class-string<Throwable>>  $exceptions
     */
    public function forExceptions(array $exceptions): static
    {
        $this->flakyExceptions = $exceptions;

        return $this;
    }

    protected function protectionsBypassed(): bool
    {
        return static::$disabledGlobally || $this->flakyProtectionDisabled;
    }

    /**
     * @param  string|array<class-string<Throwable>>|callable|null  $when
     */
    protected function normalizeRetryWhen(string|array|callable|null $when = null): ?callable
    {
        // Support for a single exception
        if (is_string($when)) {
            $when = [$when];
        }

        // Support for an array of exception types
        if (is_array($when)) {
            $when = function ($thrown) use ($when) {
                foreach ($when as $exception) {
                    if ($thrown instanceof $exception) {
                        return true;
                    }
                }

                return false;
            };
        }

        return $when;
    }

    protected function shouldThrowImmediately(?Throwable $exception = null): bool
    {
        if ($exception === null) {
            return false;
        }

        return $this->protectionsBypassed() || !$this->exceptionIsFlaky($exception);
    }

    protected function exceptionIsFlaky(Throwable $exception): bool
    {
        return $this->flakyExceptions === null || in_array($exception::class, $this->flakyExceptions, true);
    }
}
