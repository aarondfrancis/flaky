<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Flaky;

use Illuminate\Support\Traits\Macroable;
use Throwable;

class Result
{
    use Macroable;

    public readonly mixed $value;

    public readonly bool $failed;

    public readonly ?Throwable $exception;

    public readonly bool $succeeded;

    public function __construct(mixed $value, ?Throwable $exception)
    {
        $this->value = $value;
        $this->exception = $exception;
        $this->succeeded = $exception === null;
        $this->failed = !$this->succeeded;
    }

    public function throw(): static
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this;
    }
}
