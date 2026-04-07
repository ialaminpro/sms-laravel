# Contributing

Thank you for improving Laravel SMS.

## Development

Fork the repository, create a focused branch from `2.x`, and install dependencies:

```bash
composer install
composer check
```

Tests must use Laravel fakes or mocked clients; never contact a real SMS provider. Add tests for success, failure, malformed data, and security-sensitive behavior when changing a driver. Keep `SmsDriver` small and avoid introducing persistence or application billing into the transport core.

Use conventional, descriptive commits such as `feat: add provider driver` or `fix: count GSM extension septets`. Update the changelog and documentation when public behavior changes.

## Pull Requests

- Explain the user-visible behavior and compatibility impact.
- Include tests and keep PHPStan level 8 clean without a baseline or broad ignores.
- Run `composer format:test`, `composer analyse`, `composer test`, and `composer audit`.
- Never include credentials, real recipient numbers, or provider payloads.
