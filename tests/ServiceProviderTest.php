<?php

namespace Noo\StatamicLicenseHealthCheck\Tests;

use Noo\StatamicLicenseHealthCheck\Checks\StatamicLicensesCheck;
use Noo\StatamicLicenseHealthCheck\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Health\Health;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function check_is_not_registered_outside_production(): void
    {
        $check = app(Health::class)->registeredChecks()
            ->first(fn ($check) => $check instanceof StatamicLicensesCheck);

        $this->assertNull($check);
    }

    #[Test]
    public function check_is_registered_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        (new ServiceProvider($this->app))->boot();

        $check = app(Health::class)->registeredChecks()
            ->first(fn ($check) => $check instanceof StatamicLicensesCheck);

        $this->assertInstanceOf(StatamicLicensesCheck::class, $check);
    }
}
