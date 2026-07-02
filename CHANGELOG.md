# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-07-02

### Added

- Initial release: `Airwallex\Client` with lazy authentication, token caching, and refresh-before-expiry (60s leeway) against production and demo environments.
- 25 services: accounts, balances, transfers, batch transfers, wallet transfers, beneficiaries, payers, conversions, rates, FX quotes, conversion amendments, global accounts, deposits, payment intents, customers, refunds, issuing cardholders/cards/transactions/authorizations, financial transactions, settlements, reference data, webhook endpoints, and demo-only simulation helpers.
- Idempotency by default: auto-generated UUIDv4 `request_id` on money-moving create calls; explicit ids passed through unchanged.
- Automatic retries with full-jitter exponential backoff on 408/429/5xx and transport errors, honouring `Retry-After` (delta-seconds and HTTP-date); 409 conflicts are never retried; the login endpoint shares the retry budget; a 401 triggers exactly one re-login.
- Typed exceptions carrying `statusCode`, `errorCode`, `source`, `requestId`, and the parsed body; non-JSON responses raise typed errors instead of decode failures.
- Immutable, forward-compatible response objects (`AirwallexObject`) that preserve unknown fields.
- Auto-pagination via `Page::autoPagingIterator()` with a guard against `has_more` pages that return no items.
- Webhook verification (`Airwallex\Webhooks`): constant-time HMAC-SHA256 comparison, second- and millisecond-precision timestamps, 300s replay tolerance.
- Credential hygiene: API keys and bearer tokens are redacted from `var_dump()`, serialized state, and exception payloads; custom base URLs must use HTTPS (plain HTTP allowed only for localhost).
- PSR-18 client injection; the injected client is never mutated or closed.

[Unreleased]: https://github.com/Cyvid7-Darus10/airwallex-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Cyvid7-Darus10/airwallex-php/releases/tag/v0.1.0
