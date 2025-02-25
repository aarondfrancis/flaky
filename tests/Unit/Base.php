<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Flaky\Tests\Unit;

use Hammerstone\Flaky\Providers\FlakyServiceProvider;
use Hammerstone\Flaky\Tests\Support\FlakyTestServiceProvider;
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
