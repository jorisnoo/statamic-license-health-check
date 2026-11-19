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

    #[Test]
    public function an_existing_check_with_the_same_name_takes_precedence(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $existing = new class extends StatamicLicensesCheck {};
        $existing->name('StatamicLicenses');
        $health = app(Health::class)->checks([$existing]);

        (new ServiceProvider($this->app))->boot();

        $this->assertSame([$existing], $health->registeredChecks()->all());
    }

    #[Test]
    public function production_registration_can_be_disabled(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['health.register_statamic_licenses_check' => false]);

        (new ServiceProvider($this->app))->boot();

        $this->assertEmpty(app(Health::class)->registeredChecks());
    }
}
