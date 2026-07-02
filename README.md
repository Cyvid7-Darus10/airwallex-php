# airwallex-php

**Unofficial** PHP SDK for the [Airwallex API](https://www.airwallex.com/docs/api) — payouts, FX, balances, global accounts, beneficiaries, payments, issuing, and webhooks.

[![CI](https://github.com/Cyvid7-Darus10/airwallex-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Cyvid7-Darus10/airwallex-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/pastelero/airwallex?style=flat)](https://packagist.org/packages/pastelero/airwallex)
[![PHP](https://img.shields.io/packagist/dependency-v/pastelero/airwallex/php?style=flat)](https://packagist.org/packages/pastelero/airwallex)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Status: Beta](https://img.shields.io/badge/status-beta-orange.svg)](#status)

> [!IMPORTANT]
> This is an **unofficial, community-maintained** library. It is **not** affiliated with, endorsed by, or supported by Airwallex Pty Ltd — "Airwallex" is their trademark, used here only to describe compatibility. The SDK is in **beta**: the public interface may change before v1.0, so pin your version. For vendor-supported tooling, use the [official Node.js SDK](https://www.npmjs.com/package/@airwallex/node-sdk).

Airwallex's only official server-side SDK is Node.js. This library brings a Stripe-quality developer experience to PHP:

- **Modern PHP client** (PHP 8.1+, `strict_types`, named arguments) built on [Guzzle 7](https://docs.guzzlephp.org/), with any [PSR-18](https://www.php-fig.org/psr/psr-18/) client injectable
- **Automatic authentication** — token fetched on first use, cached on the client, and refreshed before expiry; no manual login calls
- **Idempotent by default** — `request_id` is auto-generated (UUIDv4) for money-moving calls, so retries never double-pay
- **Automatic retries** with full-jitter exponential backoff on 408/429/5xx/network failures (honours `Retry-After` in both seconds and HTTP-date form; 409 business conflicts are never retried)
- **Typed responses** — immutable value objects that are forward-compatible (unknown fields preserved, never dropped)
- **Auto-pagination** — iterate every page with one loop
- **Webhook signature verification** with constant-time comparison and replay protection
- **Typed exceptions** — `RateLimitException`, `AuthenticationException`, … each carrying the Airwallex error `code`, `source`, and `request_id`
- **Never leaks credentials** — API keys and tokens are redacted from `var_dump()`, serialized state, and every exception

## Installation

```bash
composer require pastelero/airwallex
```

Requires PHP 8.1+ and `ext-json`. Releases follow [semantic versioning](#status); see the [changelog](CHANGELOG.md).

## Quickstart

Create API credentials in the Airwallex web app under **Developer → API keys**, then:

```php
use Airwallex\Client;
use Airwallex\Env;

$client = new Client(
    clientId: 'your_client_id',   // or set AIRWALLEX_CLIENT_ID
    apiKey: 'your_api_key',       // or set AIRWALLEX_API_KEY
    env: Env::Demo,               // Env::Production (default) or Env::Demo sandbox
);

// Current wallet balances
foreach ($client->balances->current() as $balance) {
    echo $balance->currency, ' ', $balance->available_amount, PHP_EOL;
}
```

### Send a payout

> Payouts use `/api/v1/transfers`, which requires API version 2024-01-31 or later. If your account default is older, pass `apiVersion: '2024-01-31'` (or newer) to the client.

```php
$transfer = $client->transfers->create([
    'beneficiary_id' => 'ben_abc123',
    'source_currency' => 'USD',
    'transfer_currency' => 'PHP',
    'transfer_amount' => 5000,
    'transfer_method' => 'LOCAL',
    'reference' => 'Invoice 42',
    'reason' => 'professional_service_fees',
]);
echo $transfer->id, ' ', $transfer->status;
```

`request_id` is generated for you (pass your own to control idempotency). Airwallex will never execute the same `request_id` twice — including across the SDK's automatic retries.

### FX: quote and convert

```php
$rate = $client->rates->current(buyCurrency: 'USD', sellCurrency: 'SGD', buyAmount: 1000);
echo $rate->rate;   // indicative client rate; breakdown in $rate->rate_details

$conversion = $client->conversions->create([
    'buy_currency' => 'USD',
    'sell_currency' => 'SGD',
    'buy_amount' => 1000,
    'term_agreement' => true,
]);
```

For a bookable rate, lock a quote first and pass its id to the conversion:

```php
$quote = $client->fxQuotes->create([
    'buy_currency' => 'USD',
    'sell_currency' => 'SGD',
    'buy_amount' => 1000,
    'validity' => 'HR_1',   // MIN_1|MIN_15|MIN_30|HR_1|HR_4|HR_8|HR_24
]);
```

### Accept a payment

```php
$intent = $client->paymentIntents->create([
    'amount' => 25.00,
    'currency' => 'USD',
    'merchant_order_id' => 'order_42',
]);
$confirmed = $client->paymentIntents->confirm($intent->id, [
    'payment_method' => ['type' => 'card', 'card' => [/* ... */]],
]);
$refund = $client->refunds->create(['payment_intent_id' => $intent->id, 'amount' => 5.00]);
```

### Issue a card

```php
$cardholder = $client->issuingCardholders->create([
    'email' => 'employee@example.com',
    'individual' => ['name' => ['first_name' => 'Ada', 'last_name' => 'Lovelace']],
    'type' => 'INDIVIDUAL',
]);
$card = $client->issuingCards->create([
    'cardholder_id' => $cardholder->cardholder_id,
    'form_factor' => 'VIRTUAL',
    'created_by' => 'Ada Lovelace',
    'program' => ['purpose' => 'COMMERCIAL'],
]);
```

The PCI-scoped endpoints (`/details`, `/provision_digital_token`) are intentionally not implemented; card numbers returned by this SDK are masked.

### Verify a webhook

```php
use Airwallex\Webhooks;
use Airwallex\Exception\WebhookSignatureException;

try {
    $event = Webhooks::constructEvent(
        payload: file_get_contents('php://input'),   // the RAW body — do not re-encode
        timestamp: $_SERVER['HTTP_X_TIMESTAMP'],
        signature: $_SERVER['HTTP_X_SIGNATURE'],
        secret: $webhookSecret,
    );
} catch (WebhookSignatureException $e) {
    http_response_code(400);
    exit;
}

if ($event->name === 'transfer.settled') {
    // $event->data holds the resource payload
}
```

Signatures are compared with `hash_equals` and deliveries older than 5 minutes are rejected as replays (configurable via `toleranceSeconds`).

### Pagination

List calls return a `Page` (countable, iterable). `autoPagingIterator()` walks every page lazily:

```php
foreach ($client->beneficiaries->list()->autoPagingIterator() as $beneficiary) {
    echo $beneficiary->beneficiary_id, PHP_EOL;
}

// or page manually
$page = $client->transfers->list(status: 'PAID', pageSize: 100);
if ($page->hasMore) {
    $next = $page->nextPage();
}
```

### Test flows in the sandbox

```php
$client->simulation->createDeposit(['amount' => 1000, 'currency' => 'USD']); // demo env only
$client->simulation->transitionTransfer('tra_123', ['next_status' => 'PAID']);
```

## Escape hatch

Call any endpoint the SDK has no wrapper for — authentication, retries, and error mapping still apply:

```php
$disputes = $client->request('GET', '/api/v1/pa/payment_disputes', query: ['status' => 'OPEN']);
```

You can also bring your own PSR-18 client (proxies, mTLS, observability); it is used as-is, never mutated or closed:

```php
$client = new Client(httpClient: $myPsr18Client);
```

## Error handling

```php
use Airwallex\Exception\ApiException;
use Airwallex\Exception\ConflictException;
use Airwallex\Exception\RateLimitException;

try {
    $client->transfers->create([/* ... */]);
} catch (ConflictException $e) {
    // 409 — duplicate request_id or invalid state; never retried automatically
} catch (RateLimitException $e) {
    // 429 — the SDK already retried with backoff and gave up
} catch (ApiException $e) {
    echo $e->statusCode;   // HTTP status
    echo $e->errorCode;    // Airwallex machine-readable code
    echo $e->source;       // offending field, when provided
    echo $e->requestId;    // quote this to Airwallex support
}
```

`ConnectionException` covers transport failures; every SDK exception extends `Airwallex\Exception\AirwallexException`.

## Resources

| Service | Methods |
| --- | --- |
| `$client->accounts` | `retrieve` |
| `$client->balances` | `current`, `history` |
| `$client->transfers` | `create`, `retrieve`, `list`, `cancel`, `validate`, `confirmFunding` |
| `$client->batchTransfers` | `create`, `retrieve`, `list`, `addItems`, `deleteItems`, `items`, `quote`, `submit`, `delete` |
| `$client->walletTransfers` | `create`, `retrieve`, `list` |
| `$client->beneficiaries` | `create`, `retrieve`, `update`, `delete`, `list`, `validate` |
| `$client->payers` | `create`, `retrieve`, `update`, `delete`, `list`, `validate` |
| `$client->conversions` | `create`, `retrieve`, `list` |
| `$client->rates` | `current` |
| `$client->fxQuotes` | `create`, `retrieve` |
| `$client->conversionAmendments` | `create`, `quote`, `retrieve`, `list` |
| `$client->globalAccounts` | `create`, `retrieve`, `update`, `close`, `list`, `transactions` |
| `$client->deposits` | `list` |
| `$client->paymentIntents` | `create`, `retrieve`, `list`, `confirm`, `confirmContinue`, `capture`, `cancel` |
| `$client->customers` | `create`, `retrieve`, `update`, `list`, `generateClientSecret` |
| `$client->refunds` | `create`, `retrieve`, `list` |
| `$client->issuingCardholders` | `create`, `retrieve`, `update`, `delete`, `list` |
| `$client->issuingCards` | `create`, `retrieve`, `update`, `activate`, `limits`, `list` |
| `$client->issuingTransactions` | `retrieve`, `list` |
| `$client->issuingAuthorizations` | `retrieve`, `list` |
| `$client->financialTransactions` | `retrieve`, `list` |
| `$client->settlements` | `retrieve`, `list` |
| `$client->reference` | `supportedCurrencies`, `settlementAccounts`, `invalidConversionDates` |
| `$client->webhookEndpoints` | `create`, `retrieve`, `update`, `delete`, `list` |
| `$client->simulation` (demo only) | `createDeposit`, `settleDeposit`, `rejectDeposit`, `reverseDeposit`, `transitionTransfer`, `transitionPayment` |

Request payloads are associative arrays matching the [Airwallex API reference](https://www.airwallex.com/docs/api); list filters are named parameters plus an `extraParams` escape hatch for anything newer than this SDK.

## Laravel

The core SDK is deliberately framework-free. A thin `pastelero/airwallex-laravel` bridge (service provider, `config/airwallex.php`, facade, webhook middleware) is planned — see [docs/laravel-bridge.md](docs/laravel-bridge.md) for the design.

## Status

**Beta.** The SDK follows [semantic versioning](https://semver.org); expect possible breaking changes in minor `0.x` releases, so pin a minor version:

```json
{ "require": { "pastelero/airwallex": "~0.1.0" } }
```

Behavior is modelled on (and tested against the same matrix as) the [airwallex-python](https://github.com/Cyvid7-Darus10/airwallex-python) SDK. Endpoint paths and parameters are grounded in the Airwallex OpenAPI schema and current documentation.

## Development

```bash
composer install
composer test            # PHPUnit
composer stan            # PHPStan (level max)
composer cs              # php-cs-fixer (check only)
composer test:coverage   # coverage report + clover
```

See [CONTRIBUTING.md](CONTRIBUTING.md) and [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE) © Cyrus David Pastelero
