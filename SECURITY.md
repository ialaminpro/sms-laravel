# Security Policy

## Supported Versions

Security fixes are provided for the latest `2.x` release. Legacy `1.x` is unsupported and contains known architectural risks documented in the upgrade guide.

## Reporting a Vulnerability

Please use GitHub's private security advisory feature for this repository. If that is unavailable, email the maintainer at ialamin.pro@gmail.com with the subject `sms-laravel security report`.

Do not open a public issue. Include reproduction steps and affected versions, but redact API keys, tokens, full phone numbers, SMS bodies, and customer data. You should receive an acknowledgement within seven days. A fix and disclosure timeline will be coordinated after validation.

## Operational Guidance

- Store credentials in environment-backed configuration and rotate exposed keys.
- Do not log full request payloads, message bodies, or recipient numbers by default.
- Review provider data processing, retention, retry, and idempotency behavior before production use.
- Keep Composer dependencies current and run `composer audit` in CI.
