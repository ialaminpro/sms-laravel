# Releasing

Releases are a maintainer-only operation. Never tag from a dirty worktree.

1. Confirm CI passes on all supported PHP/Laravel combinations.
2. Run `composer validate --strict`, a clean `composer install --prefer-dist --no-interaction`, `composer format:test`, `composer analyse`, `composer test`, and `composer audit`.
3. Review `UPGRADE.md`, configuration defaults, supported versions, and the `CHANGELOG.md` Unreleased section.
4. Test the bundled provider integration manually with a dedicated non-production account; automated tests intentionally use fakes.
5. Set the release date, compare against the previous tag, and confirm no credentials or generated dependencies are tracked.
6. Create a signed semantic-version tag and GitHub release only after review.
7. Confirm Packagist receives the release and that installation works in a fresh supported Laravel application.

For the current `2.x` branch, the planned first stable tag is `2.0.0`. This repository does not tag or publish releases automatically.

## Current Provider Blocker

On 2026-08-23, the public OnnoRokom WSDL confirmed the `NumberSms` SOAP contract, but repeated TLS-verified requests to the service endpoint subsequently failed with an incomplete/untrusted issuer chain. Do not bypass verification. A credentialed non-production delivery test remains blocked until the provider repairs TLS and supplies a sandbox account.
