<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Flaky\Tests\Unit;

use AaronFrancis\Flaky\Providers\FlakyServiceProvider;
use AaronFrancis\Flaky\Tests\Support\FlakyTestServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class Base extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetup($app) {}

    protected function getPackageProviders($app)
    {
        return [
            FlakyServiceProvider::class,
            FlakyTestServiceProvider::class,
        ];
    }
}
