<?php

namespace Noo\StatamicLicenseHealthCheck;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Noo\StatamicLicenseHealthCheck\Checks\StatamicLicensesCheck;
use Spatie\Health\Checks\Check;
use Spatie\Health\Facades\Health;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->isProduction() || ! config('health.register_statamic_licenses_check', true)) {
            return;
        }

        $this->app->booted(function (): void {
            $check = StatamicLicensesCheck::new();

            if (! Health::registeredChecks()->contains(fn (Check $registered) => $registered->getName() === $check->getName())) {
                Health::checks([$check]);
            }
        });
    }
}
