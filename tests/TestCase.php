<?php

namespace Noo\StatamicLicenseHealthCheck\Tests;

use Noo\StatamicLicenseHealthCheck\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Health\HealthServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HealthServiceProvider::class,
            ServiceProvider::class,
        ];
    }
}
