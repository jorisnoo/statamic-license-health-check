# Changelog

All notable changes to this project will be documented in this file.

## [0.1.0] - 2026-11-19

### Added

- First tagged release. Production auto-registration respects an existing `StatamicLicenses` check and can be disabled in `config/health.php`. Applications tracking `dev-main` can now require `^0.1.0`.

### Fixed

- Outpost connection failures, rate limits and server errors are skipped with a reason and error code. Invalid key files and rejected requests now warn instead of silently skipping.
- Incomplete or unreadable license responses no longer fail the license or interrupt the other health checks.
