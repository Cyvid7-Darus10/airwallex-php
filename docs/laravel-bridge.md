# Design: pastelero/airwallex-laravel

A planned, separate bridge package. The core SDK stays framework-free; the
bridge is a thin adapter layer only — no request logic, retries, or crypto of
its own.

## Package layout

```
airwallex-laravel/
├── composer.json            # requires pastelero/airwallex + illuminate/support
├── config/airwallex.php
└── src/
    ├── AirwallexServiceProvider.php
    ├── Facades/Airwallex.php
    └── Http/Middleware/VerifyAirwallexWebhook.php
```

## Service provider

- Binds `Airwallex\Client` as a singleton built from `config/airwallex.php`.
- Publishes the config file (`php artisan vendor:publish --tag=airwallex-config`).

```php
// config/airwallex.php
return [
    'client_id' => env('AIRWALLEX_CLIENT_ID'),
    'api_key' => env('AIRWALLEX_API_KEY'),
    'env' => env('AIRWALLEX_ENV', 'production'),     // 'production' | 'demo'
    'api_version' => env('AIRWALLEX_API_VERSION'),
    'on_behalf_of' => env('AIRWALLEX_ON_BEHALF_OF'),
    'timeout' => env('AIRWALLEX_TIMEOUT', 60.0),
    'max_retries' => env('AIRWALLEX_MAX_RETRIES', 2),
    'webhook_secret' => env('AIRWALLEX_WEBHOOK_SECRET'),
];
```

## Facade

`Airwallex::transfers()->create([...])` proxying to the bound client. The
facade is sugar only; constructor injection of `Airwallex\Client` remains the
documented default.

## Webhook handling

`VerifyAirwallexWebhook` middleware (or a `Route::airwallexWebhook()` macro):

1. Reads the **raw** request body via `$request->getContent()` — never the
   parsed/re-encoded JSON, which would break the signature.
2. Calls `Webhooks::constructEvent(...)` with `x-timestamp`, `x-signature`,
   and `config('airwallex.webhook_secret')`.
3. Aborts 400 on `WebhookSignatureException`; otherwise stores the
   `WebhookEvent` on the request (`$request->attributes->set('airwallex_event', $event)`).

## Explicit non-goals

- No Eloquent models, queues, or event mapping — application concerns.
- No HTTP behavior changes: retries/idempotency/errors live in the core SDK
  so both packages stay in lockstep via composer versioning (`~0.1`).
