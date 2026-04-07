# Upgrade Guide

## Upgrading from 1.x to 2.x

Version 2 separates reusable SMS transport from the original application's billing and persistence rules. Review every item below before deployment.

### Runtime requirements

Upgrade to PHP 8.2+ and Laravel 12–13, then require `acolyte/sms-laravel:^2.0`. Publish the new configuration with `php artisan vendor:publish --tag=sms-config` and replace the generic 1.x environment variables with the `SMS_ONNOROKOM_*` variables documented in the README.

### Sending API

Replace the array and JSON-string API:

```php
use Acolyte\SmsLaravel\SMS;

$json = SMS::send([
    'mobile' => '+49123456789',
    'smsText' => 'Hello',
    'mask' => 'Acme',
    'campaign' => 'order-123',
    'client_id' => 'client-1',
]);
```

with the typed API:

```php
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Facades\Sms;

$result = Sms::send(new SmsMessage(
    to: '+49123456789',
    body: 'Hello',
    sender: 'Acme',
    clientReference: 'order-123',
));
```

The compatibility adapter still accepts `mobile`, `smsText`, `mask`, and `campaign` and returns a JSON string. `client_id` is ignored because transport no longer owns client accounts. The adapter is deprecated and planned for removal in the next major version.

### Provider integration

The bundled driver sends the provider's published SOAP `NumberSms` request through Laravel's HTTP client. The 1.x query-string protocol is not retained because it exposed API keys in URLs. The API key now travels inside the XML request body over HTTPS. If using a different provider, implement `SmsDriver`; do not put credentials into query parameters.

### Billing and balances

The `sms` account table, `SMSModel`, `SMS::checkBalance()`, rate calculation, and automatic balance decrement were business-specific and have been removed. Keep existing tables only while your application migrates. Implement billing in application services or listeners around `SmsSending`, `SmsSent`, and `SmsFailed`.

For atomic balance reservations, reserve before calling the manager and finalize or release after the result. Application code must define its own transaction and idempotency rules.

### Logging and database schema

The `sms_logs` migration and automatic writes were removed. Existing data is not deleted during upgrade. Add application listeners if persistence is required, with an explicit retention/redaction policy. The package no longer publishes or runs migrations.

### Removed web UI

The `/timezones/{timezone?}` and `/acolyte/clients` routes, controller, package view, and `App\Http\Controllers\Controller` dependency were removed. Remove links to those routes or replace them with application-owned administration screens.

### Result and error handling

Do not parse arbitrary response strings. Check `$result->successful`, `$result->providerMessageId`, `$result->errorCode`, and `$result->errorMessage`. See the README for stable error categories. Queued failures raise `SmsSendingFailed` for Laravel retry handling.

### Segment counts

Replace `ceil(strlen($text) / 160)` with `SmsSegmentCalculator`. Counts can change for GSM extension characters, Unicode, emoji, and concatenated messages; update any application billing tests accordingly.

### Deployment checklist

1. Test the provider endpoint and response mapping in a non-production account.
2. Migrate all synchronous call sites to `SmsMessage` and `SmsResult`.
3. Move billing and logs into application-owned services/listeners.
4. Configure queue workers if using `Sms::queue()`.
5. Verify redaction in logs and observability tools.
6. Remove the compatibility adapter calls before the next major version.
