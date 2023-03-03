<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Flaky;

use Illuminate\Support\Traits\Macroable;

class Result
{
    use Macroable;

    public $value;

    public $failed = false;

    public $exception;

    public $succeeded;

    public function __construct($value, $exception)
    {
        $this->value = $value;

        $this->succeeded = is_null($exception);

        $this->failed = !$this->succeeded;

        $this->exception = $exception;
    }

    public function throw()
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this;
    }
}
