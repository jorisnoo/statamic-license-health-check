# Statamic License Health Check

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jorisnoo/statamic-license-health-check.svg?style=flat-square)](https://packagist.org/packages/jorisnoo/statamic-license-health-check)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jorisnoo/statamic-license-health-check/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jorisnoo/statamic-license-health-check/actions?query=workflow%3ATests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jorisnoo/statamic-license-health-check.svg?style=flat-square)](https://packagist.org/packages/jorisnoo/statamic-license-health-check)

A production health check for Statamic licenses, built for `spatie/laravel-health` and Oh Dear.

The check fails on invalid Statamic or addon licenses. A connection error, HTTP 429, or HTTP 5xx from Outpost skips the check and records the reason and code. Other request errors, an unreadable license key file, and responses with no license verdict warn instead of claiming the license is invalid. Check the application log if the licensing response could not be processed.

## Installation

```bash
composer require 'jorisnoo/statamic-license-health-check:^0.1.0'
```

The health check is discovered automatically and registered in production environments.

To keep a skipped check from producing HTTP 503 on Spatie's **simple** health endpoint, or a failure status on its **JSON** endpoint when `json_results_failure_status` is configured, set the following in `config/health.php`. This setting does not change the HTML results page.

```php
'treat_skipped_as_failure' => false,
```

### Migrating from an in-app check or `dev-main`

If a provider registers a check named `StatamicLicenses` during registration or boot, the package leaves it in place. Remove the old registration and class to switch to the packaged check. To keep full control of registration (including checks registered later), set `'register_statamic_licenses_check' => false` in `config/health.php`. Rebuild the configuration cache when deploying that change.

Replace a `dev-main` constraint by running the installation command above; review and commit the consuming application's updated Composer lock file.

## Testing

```bash
composer test
```

## License

The MIT License. See [LICENSE.md](LICENSE.md).

## Maintainer releases

See [RELEASING.md](RELEASING.md) for versioning, changelog entries and the release command.
