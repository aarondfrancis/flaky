<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Flaky;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\Traits\Macroable;

/**
 * @mixin Flaky
 */
class FlakyCommand
{
    use Macroable;

    protected Command $command;

    /** @var array<string>|false */
    protected array|false $varyOnInput = false;

    public static function make(Command $command): static
    {
        return new static($command);
    }

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * @param  array<string>  $keys
     */
    public function varyOnInput(array $keys = []): static
    {
        $this->varyOnInput = $keys;

        return $this;
    }

    public function instance(): Flaky
    {
        return Flaky::make($this->generateCommandId())
            // Only enable protection if the command is running
            // via schedule not directly from CLI.
            ->disableFlakyProtection($disabled = !$this->isScheduledCommand());
    }

    protected function generateCommandId(): string
    {
        return implode('-', array_filter([
            'command',
            $this->command->getName(),
            $this->hashInput(),
        ]));
    }

    protected function isScheduledCommand(): bool
    {
        // See the FlakyServiceProvider to see where this is coming from.
        return Env::get('IS_SCHEDULED') === '1';
    }

    protected function hashInput(): string
    {
        if ($this->varyOnInput === false) {
            return '';
        }

        $input = array_merge(
            $this->command->arguments(),
            $this->command->options()
        );

        if (count($this->varyOnInput) > 0) {
            $input = Arr::only($input, $this->varyOnInput);
        }

        ksort($input);

        return md5(json_encode($input));
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->instance()->{$name}(...$arguments);
    }
}
