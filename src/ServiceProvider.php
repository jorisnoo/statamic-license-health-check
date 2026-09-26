<?php

namespace Noo\StatamicLicenseHealthCheck;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Noo\StatamicLicenseHealthCheck\Checks\StatamicLicensesCheck;
use Spatie\Health\Facades\Health;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->isProduction()) {
            return;
        }

        Health::checks([
            StatamicLicensesCheck::new(),
        ]);
    }
}
