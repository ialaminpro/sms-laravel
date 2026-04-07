# Changelog

All notable changes are documented here. This project follows [Semantic Versioning](https://semver.org/) and the structure of [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added

- Laravel-style `SmsManager`, driver selection, and runtime driver extension.
- Immutable `SmsMessage`, `SmsResult`, and `SmsSegments` objects.
- GSM-7 and UCS-2 segment calculation, including extension characters and surrogate pairs.
- Queue job, lifecycle events, Laravel notification channel, Testbench suite, level-8 static analysis, and multi-version CI.

### Changed

- PHP now requires 8.2 or newer; supported Laravel versions are 12–13.
- OnnoRokom transport now uses its published SOAP contract through Laravel's HTTP client, with body authentication, timeouts, and bounded retries.
- Expected provider errors now return stable typed failure codes.

### Fixed

- Replaced byte-length segment counting with encoding-aware transport-unit calculation.
- Removed host `App\` namespace assumptions and the incorrect legacy migration rollback.

### Security

- API keys are no longer placed in request URLs.
- Provider response bodies and raw exception details are not exposed through results or events.
- Database logging is opt-in through application event listeners.

### Deprecated

- `Acolyte\SmsLaravel\SMS::send(array)` remains as a temporary migration adapter; use the typed facade API.

### Removed

- Mandatory client balance/accounting tables, SMS log writes, package routes, controller, Eloquent model, migrations, and admin view.
- Legacy public `SMS::checkBalance()` and `SMS::getUrl()` helpers.

## [1.0.2] - 2020-01-15

Historical 1.x release. See the Git history for legacy changes.

[Unreleased]: https://github.com/ialaminpro/sms-laravel/compare/1.0.2...2.x
[1.0.2]: https://github.com/ialaminpro/sms-laravel/releases/tag/1.0.2
