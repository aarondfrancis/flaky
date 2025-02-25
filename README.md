# Flaky for Laravel

Flaky for Laravel is a package that helps you handle operations that may have intermittent failures due to unreliable
third-parties.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aaronfrancis/flaky)](https://packagist.org/packages/aaronfrancis/flaky)
[![Total Downloads](https://img.shields.io/packagist/dt/aaronfrancis/flaky)](https://packagist.org/packages/aaronfrancis/flaky)
[![License](https://img.shields.io/packagist/l/aaronfrancis/flaky)](https://packagist.org/packages/aaronfrancis/flaky)

## Installation

You can install the package via Composer

```console
composer require aaronfrancis/flaky
```

## Usage

Let's say you have a flaky piece of code that fails 20% of the time:

```php
if (Lottery::odds(1 / 5)->choose()) {
    throw new Exception("Oops");
}
```

But you don't care if it fails, as long as it doesn't fail for more than an hour. Then you could wrap that code up in Flaky
protections.

```php
Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    ->run(function() {
        if (Lottery::odds(1 / 5)->choose()) {
            throw new Exception("Oops");
        }
    })
```

Now, exceptions will be silenced unless the operation hasn't succeeded in an hour.

Each instance of flaky code requires a unique ID passed through to the `make` method. This is how we keep track of
failures over time. You can make up whatever you want, it's just a cache key.

Flaky uses your default cache store. That may need to be configurable in the future.

## Throwing Exceptions

You have several different ways to control when exceptions are thrown:

### Time Based

If you want to throw an exception after a certain period of time, you have several methods available to you.

- `allowFailuresForAMinute()`
- `allowFailuresForMinutes($minutes)`
- `allowFailuresForAnHour()`
- `allowFailuresForHours($hours)`
- `allowFailuresForADay()`
- `allowFailuresForDays($days)`
- `allowFailuresFor($seconds = 0, $minutes = 0, $hours = 0, $days = 0)`

If your callback throws an exception, Flaky will check to see if it's still within the grace period. If it is, the exception
will be captured.

If your callback succeeds, the deadline will be reset.

### Consecutive Failures

If you'd prefer to take a numeric approach instead of a time-based approach, you can use the `allowConsecutiveFailures`
method.

```php
Flaky::make('my-flaky-code')
    // It can fail ten times in a row. 
    ->allowConsecutiveFailures(10)
    ->run(function() {
        //
    })
```

Now your function can fail 10 times in a row without alerting you, but on the 11th failure the exception will be thrown. If the callback succeeds, the consecutive failure counter will be reset.

### Total Failures

If you want to throw an exception after a total number of failures, regardless of successes, you can use
the `allowTotalFailures` method.

```php
Flaky::make('my-flaky-code')
    // It can fail ten times total. 
    ->allowTotalFailures(10)
    ->run(function() {
        //
    })
```

On the 11th failure, the exception will be thrown. The counter will be reset only after the exception has been thrown,
but not for successful invocations. You can think of this as "Throw every 11th exception, regardless of successes."

### Combining

You can combine the three methods in any way you like.

```php
Flaky::make('my-flaky-code')
    // Alert after an hour. 
    ->allowFailuresForAnHour()
    // Alert after the third consecutive failure.
    ->allowConsecutiveFailures(3)
    // Alert after the tenth failure. 
    ->allowTotalFailures(10)
    ->run(function() {
        //
    })
```

## Reporting instead of throwing

By default, Flaky will actually `throw` the exception if it occurs outside of the bounds you have define. You can choose
to `report` that exception instead of throw it, using Laravel's `report` method.

```php
Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    // Don't throw, but use `report()` instead.
    ->reportFailures()
    ->run(function() {
        //
    })
```

This allows you to still get the alert, but carry on processing if you need to. (This is helpful for loops or
long-running processes.)

## Retrying

If you want to immediately retry a bit of flaky code, you can use the `retry` method, which uses Laravel's `retry`
helper under the hood. Any failures that happen as a part of the retry process don't count toward the total or
consecutive failures. If your function is retried the maximum times and does not succeed, then that counts as one
failure.

```php
Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    // Retry 3 times, with 500ms between.
    ->retry(3, 500)
    ->run(function() {
        //
    })
```

You can also choose to retry a _single type_ of exception

```php
Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    // Only retry TimeoutExceptions
    ->retry(3, 500, TimeoutException::class)
    ->run(function() {
        //
    })
```

Or multiple types of exceptions

```php
Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    // Only retry TimeoutExceptions and FooBarExceptions
    ->retry(3, 500, [TimeoutException::class, FooBarException::class])
    ->run(function() {
        //
    })
```

Or pass through your own method:

```php
Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    // Pass through your own $when callback
    ->retry(3, 500, function($exception) {
        // 
    })
    ->run(function() {
        //
    })
```

## Accessing the result

Flaky will return a `Result` class for your use.

```php
$result = Flaky::make('my-flaky-code')
    ->allowFailuresForAnHour()
    ->run(function() {
        return 1;
    });

$result->value; // 1
$result->failed; // false
$result->succeeded; // true
$result->exception; // null. Would be populated if an exception was thrown.
$result->throw(); // Throws an exception if present. Is a noop if not.
```

## Flaky Commands

If you have entire commands that are Flaky, you can use the `FlakyCommand` class as a convenience.

```php
class FlakyTestCommand extends Command
{
    protected $signature = 'flaky {--arg=} {--flag}';

    public function handle()
    {
        FlakyCommand::make($this)
            ->allowFailuresForAnHour()
            ->run([$this, 'process']);
    }

    public function process()
    {
        throw new Exception('oops');
    }
}
```

This command will now have Flaky protections, but *only* when invoked by the scheduler. If you run this command
manually, Flaky is not engaged and you'll get all the exceptions as you would have otherwise.

### Flaky Command IDs

The `FlakyCommand::make($this)` call will set the unique Flaky ID for you, based on the command's signature. By default,
every invocation of an command is treated under the same key. If you want to vary that based on user input, you can use
the `varyOnInput` method.

```php
class FlakyTestCommand extends Command
{
    protected $signature = 'flaky {--arg=} {--flag}';

    public function handle()
    {
        FlakyCommand::make($this)
            ->allowFailuresForAnHour()
            // Consider the `arg` and `flag` input when creating the unique ID.
            ->varyOnInput()
            ->run([$this, 'process']);
    }
}
```

If you want to vary on particular input instead of all the input, you can pass an array of keys. This is useful for when
each `--arg` should have its own flaky protections, but varying the `--flag` shouldn't create a unique set of
protections.

```php
class FlakyTestCommand extends Command
{
    protected $signature = 'flaky {--arg=} {--flag}';

    public function handle()
    {
        FlakyCommand::make($this)
            ->allowFailuresForAnHour()
            // Consider only the `arg` input when creating the unique ID.
            ->varyOnInput(['arg'])
            ->run([$this, 'process']);
    }
}
```

## License

The MIT License (MIT).

## Support

This is free! If you want to support me:

- Sponsor my open source work: [aaronfrancis.com/backstage](https://aaronfrancis.com/backstage)
- Check out my courses:
    - [Mastering Postgres](https://masteringpostgres.com)
    - [High Performance SQLite](https://highperformancesqlite.com)
    - [Screencasting](https://screencasting.com)
- Help spread the word about things I make

## Credits

Flaky was developed by Aaron Francis. If you like it, please let me know!

- Twitter: https://twitter.com/aarondfrancis
- Website: https://aaronfrancis.com
- YouTube: https://youtube.com/@aarondfrancis
- GitHub: https://github.com/aarondfrancis/solo
