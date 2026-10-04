# Statamic License Health Check

A production health check for Statamic licenses, built for `spatie/laravel-health` and Oh Dear.

The check reports invalid Statamic and addon licenses as failures. Temporary communication failures with Statamic's Outpost service are reported as skipped, avoiding false license alerts during upstream outages.

## Installation

```bash
composer require jorisnoo/statamic-license-health-check
```

The health check is discovered automatically and registered in production environments.

When using the local health status page, configure skipped checks as non-failures in `config/health.php`:

```php
'treat_skipped_as_failure' => false,
```

## Testing

```bash
composer test
```

## License

The MIT License. See [LICENSE.md](LICENSE.md).

## Maintainer releases

See [RELEASING.md](RELEASING.md) for versioning, changelog entries and the release command.
