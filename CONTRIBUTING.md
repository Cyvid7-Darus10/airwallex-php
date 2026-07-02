# Contributing

Thanks for helping improve airwallex-php! This is an unofficial,
community-maintained SDK — contributions of all sizes are welcome.

## Getting started

```bash
git clone https://github.com/Cyvid7-Darus10/airwallex-php.git
cd airwallex-php
composer install
```

## Before you open a PR

All three gates must be green — CI enforces them on PHP 8.1 through 8.5:

```bash
composer cs      # php-cs-fixer, check only (composer cs:fix to apply)
composer stan    # PHPStan at level max
composer test    # PHPUnit (no network access — tests use Guzzle MockHandler)
```

Coverage must stay at or above 85% lines (`composer test:coverage`).

## Guidelines

- **Behavior is grounded in the Airwallex API.** New endpoints must match the
  current API reference; note the docs URL in the PR description.
- **No network calls in tests.** Use the Guzzle `MockHandler` helpers in
  `tests/TestCase.php`.
- **Money-moving creates take `request_id`.** If you add one, wire it through
  `Util::ensureRequestId()` and extend `IdempotencyTest`.
- **Path parameters must be escaped** with `Util::encodePathParam()` — there
  is a test that sweeps every parameterized endpoint.
- **Never let credentials into exceptions, dumps, or serialized state.**
- Follow [Conventional Commits](https://www.conventionalcommits.org)
  (`feat:`, `fix:`, `docs:`, `test:`, `chore:`, ...), one logical change per PR.

## Releasing (maintainers)

1. Update `CHANGELOG.md` and `Client::VERSION`.
2. Tag: `git tag vX.Y.Z && git push --tags`.
3. Packagist picks the tag up automatically.
