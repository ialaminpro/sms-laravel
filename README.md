# Laravel SMS

[![CI](https://github.com/ialaminpro/sms-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/ialaminpro/sms-laravel/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/acolyte/sms-laravel.svg)](https://packagist.org/packages/acolyte/sms-laravel)
[![Downloads](https://img.shields.io/packagist/dt/acolyte/sms-laravel.svg)](https://packagist.org/packages/acolyte/sms-laravel)
[![PHP](https://img.shields.io/packagist/php-v/acolyte/sms-laravel.svg)](composer.json)
[![Laravel 12–13](https://img.shields.io/badge/Laravel-12–13-ff2d20.svg)](composer.json)
[![License](https://img.shields.io/packagist/l/acolyte/sms-laravel.svg)](LICENSE)

A Laravel-native multi-provider SMS gateway manager with driver switching, typed messages/results, queue support, events, provider failure handling, and accurate SMS segmentation.

## Overview

Laravel SMS keeps provider transport small and replaceable. Applications send immutable `SmsMessage` objects through a Laravel-style manager and receive a predictable `SmsResult`; billing, persistence, and provider-specific payloads remain outside the core API.

```text
Application
    │
    ▼
Sms Facade / SmsManager ─────► SmsSending
    │                         ├► SmsSent
    ├── synchronous send      └► SmsFailed
    └── queued send
    │
    ▼
SmsDriver
    ├── bundled provider driver
    └── custom driver
    │
    ▼
Laravel HTTP Client
```

## Features

- Laravel manager with default, explicitly selected, and runtime-extended drivers
- Immutable, validated messages and typed success/failure results
- GSM-7, GSM-7 extension-table, and UCS-2 multipart calculation
- Sanitized provider failure categories, timeouts, and bounded retries
- Queue job with configurable connection, queue, attempts, and backoff
- `SmsSending`, `SmsSent`, and `SmsFailed` events
- Laravel notification channel and `routeNotificationForSms()` support
- No required database, migrations, routes, controllers, or live-provider tests

## Requirements

- PHP 8.2 or newer
- Laravel 12 or 13

Laravel 13 combinations require a PHP version accepted by Laravel 13. See the CI matrix for the combinations continuously tested by this branch.

## Installation

```bash
composer require acolyte/sms-laravel:^2.0
php artisan vendor:publish --tag=sms-config
```

Laravel discovers the service provider and `Sms` facade automatically.

## Configuration

Add provider-specific values to `.env`:

```dotenv
SMS_DRIVER=onnorokom
SMS_ONNOROKOM_URL=https://api2.onnorokomsms.com/sendsms.asmx
SMS_ONNOROKOM_API_KEY=replace-me
SMS_ONNOROKOM_SENDER=Acme
SMS_ONNOROKOM_CONNECT_TIMEOUT=3
SMS_ONNOROKOM_TIMEOUT=10
SMS_ONNOROKOM_RETRIES=2
SMS_ONNOROKOM_RETRY_DELAY=200
```

The bundled `onnorokom` driver implements the provider's published SOAP `NumberSms` contract. Its API key is XML-escaped inside the HTTPS request body and is never added to the request URL. The driver automatically selects the provider's `TEXT` or `UCS` mode using the segment calculator.

Do not disable TLS certificate verification. Confirm the provider endpoint has a valid, trusted certificate chain before production deployment; if it does not, contact the provider or select a custom driver.

## Quick Start

```php
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Facades\Sms;

$result = Sms::send(new SmsMessage(
    to: '+49123456789',
    body: 'Your verification code is 482913.',
    sender: 'Acme',
    clientReference: 'verification-8f32',
));

if (! $result->successful) {
    report("SMS failed: {$result->errorCode}");
}
```

Whitespace is removed from recipient numbers. Use E.164-formatted numbers; the package intentionally does not guess country codes or maintain unreliable country-specific regular expressions.

## Sending SMS

`SmsMessage` accepts the recipient, body, and optional sender and client reference. Empty recipients, empty bodies, and invalid UTF-8 are rejected with `InvalidArgumentException` before a provider call.

```php
$result = Sms::send(new SmsMessage('+49123456789', 'Hello from Laravel.'));
```

## Selecting Drivers

Pass a driver name to `send()` or resolve a driver directly:

```php
$result = Sms::send($message, driver: 'backup');
$result = Sms::driver('backup')->send($message);
```

## Custom Drivers

Implement the deliberately small contract and register the driver during application boot:

```php
use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\Facades\Sms;

final class BackupSmsDriver implements SmsDriver
{
    public function send(SmsMessage $message): SmsResult
    {
        // Call the provider through an injected client.
        return SmsResult::success(providerMessageId: 'provider-id');
    }
}

Sms::extend('backup', fn ($app) => $app->make(BackupSmsDriver::class));
```

Custom drivers should return typed failures for expected provider outcomes and avoid putting credentials or complete raw payloads in metadata.

## SMS Result

Every synchronous send returns `SmsResult`:

```php
$result->successful;        // bool
$result->providerMessageId; // ?string
$result->errorCode;         // ?string
$result->errorMessage;      // ?string
$result->metadata;          // array<string, mixed>
```

Drivers can use `SmsResult::success()` and `SmsResult::failure()` to preserve those invariants.

## SMS Segmentation

```php
use Acolyte\SmsLaravel\Support\SmsSegmentCalculator;

$segments = (new SmsSegmentCalculator)->calculate('Balance: 10€');

$segments->encoding;       // GSM-7
$segments->characters;     // visible Unicode code points
$segments->units;          // encoded septets or UTF-16 code units
$segments->segments;       // billable transport segments
$segments->unitsPerSegment;
```

GSM-7 messages use 160 septets for one part and 153 per concatenated part. Extension-table characters (`^`, `{`, `}`, `\\`, `[`, `]`, `~`, `|`, `€`) consume two septets. Any unsupported GSM character selects UCS-2, with 70 UTF-16 code units for one part and 67 per concatenated part. Non-BMP characters such as emoji consume a UTF-16 surrogate pair (two units). An empty string calculates as zero segments, although `SmsMessage` does not allow an empty body.

## Queues

Queueing serializes only the typed message and selected driver name. The worker resolves the driver when the job executes, so runtime driver configuration remains authoritative.

```php
Sms::queue(new SmsMessage('+49123456789', 'Queued message'));
Sms::queue($message, driver: 'backup');
```

Configure `SMS_QUEUE_CONNECTION`, `SMS_QUEUE`, and `SMS_QUEUE_TRIES` as needed. Failed results throw a sanitized `SmsSendingFailed` exception so Laravel can apply the job's configured attempts and backoff (`10`, `60`, and `300` seconds by default).

## Events

The manager dispatches `SmsSending` before transport. It then dispatches exactly one of `SmsSent` or `SmsFailed`. Events contain the message, driver name, typed result where applicable, and an immutable timestamp—never credentials.

```php
use Acolyte\SmsLaravel\Events\SmsFailed;
use Illuminate\Support\Facades\Event;

Event::listen(SmsFailed::class, function (SmsFailed $event): void {
    logger()->warning('SMS delivery failed', [
        'driver' => $event->driver,
        'code' => $event->result->errorCode,
    ]);
});
```

Avoid logging complete phone numbers or message bodies unless your privacy and retention policy explicitly permits it.

## Laravel Notifications

Return `'sms'` from `via()` and implement `SmsNotification`. A notification can return a complete `SmsMessage`, or a body string that uses `routeNotificationForSms()` on the notifiable.

```php
use Acolyte\SmsLaravel\Contracts\SmsNotification;
use Illuminate\Notifications\Notification;

final class InvoicePaid extends Notification implements SmsNotification
{
    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    public function toSms(object $notifiable): string
    {
        return 'We received your payment. Thank you.';
    }
}

public function routeNotificationForSms(Notification $notification): string
{
    return $this->phone_number;
}
```

## Error Handling

Expected failures are represented as unsuccessful results. The bundled driver maps configuration errors, invalid requests, authentication failures, insufficient provider balance, rate limits, timeouts, network failures, server errors, malformed responses, and unknown provider errors to stable codes. HTTP status and provider code may appear in limited metadata; raw bodies and credentials do not.

Unexpected custom-driver exceptions are converted to `driver_exception` without copying exception details. Validate and monitor failures using `SmsFailed` rather than parsing provider strings.

## Logging

Database logging is not required or bundled. Persist or observe sends with event listeners appropriate to your application. This keeps migrations, retention rules, encryption, and redaction under application control.

## Configuration Reference

| Key | Environment variable | Default | Purpose |
| --- | --- | --- | --- |
| `default` | `SMS_DRIVER` | `onnorokom` | Default driver name |
| `drivers.onnorokom.base_url` | `SMS_ONNOROKOM_URL` | official SOAP endpoint | Provider service endpoint |
| `drivers.onnorokom.api_key` | `SMS_ONNOROKOM_API_KEY` | empty | Secret credential |
| `drivers.onnorokom.sender` | `SMS_ONNOROKOM_SENDER` | `null` | Default mask/sender |
| `drivers.onnorokom.connect_timeout` | `SMS_ONNOROKOM_CONNECT_TIMEOUT` | `3` | Connection timeout in seconds |
| `drivers.onnorokom.timeout` | `SMS_ONNOROKOM_TIMEOUT` | `10` | Total timeout in seconds |
| `drivers.onnorokom.retries` | `SMS_ONNOROKOM_RETRIES` | `2` | Transient retry count |
| `drivers.onnorokom.retry_delay` | `SMS_ONNOROKOM_RETRY_DELAY` | `200` | Retry delay in milliseconds |
| `queue.connection` | `SMS_QUEUE_CONNECTION` | `null` | Laravel queue connection |
| `queue.queue` | `SMS_QUEUE` | `null` | Queue name |
| `queue.tries` | `SMS_QUEUE_TRIES` | `3` | Job attempt limit |

## Engineering Decisions

- The driver contract isolates provider protocols while keeping extension inexpensive.
- Sending is transport; the legacy client billing and balance tables were application-specific and are no longer mandatory.
- Segmentation is explicit domain logic because encoding changes billable part counts.
- HTTP calls live inside drivers so consumers and tests never depend on provider response formats.
- Persistence is event-driven and optional because applications own privacy and retention requirements.
- Provider tests use Laravel HTTP fakes to remain deterministic and prevent accidental live sends.
- Queued jobs resolve driver names at execution so deploy-time configuration and failover bindings remain current.

## Testing

```bash
composer format:test
composer analyse
composer test
composer audit
# or all checks:
composer check
```

No automated test makes a live provider request.

## Compatibility

| Package | PHP | Laravel |
| --- | --- | --- |
| `1.x` | legacy | legacy |
| `2.x` | `^8.2` | `^12`, `^13` |

## Upgrading

Version 2 is a deliberate major-version redesign. The deprecated `Acolyte\SmsLaravel\SMS::send(array)` adapter remains for staged migration, but database balance checks, automatic log writes, controller routes, views, models, migrations, `checkBalance()`, and `getUrl()` were removed. Read [UPGRADE.md](UPGRADE.md) before upgrading.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Changes must pass formatting, level-8 analysis, tests, and dependency audit.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md). Do not open public issues containing credentials, phone numbers, or SMS bodies.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

Laravel SMS is open-source software licensed under the [MIT license](LICENSE).
