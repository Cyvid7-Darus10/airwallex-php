<?php

declare(strict_types=1);

/**
 * Bring your own PSR-18 client — proxies, mTLS, observability middleware.
 *
 * The SDK uses the injected client as-is: it is never reconfigured, mutated,
 * or closed, so you keep full control of connection behavior. Timeouts are
 * then YOUR client's responsibility (the SDK's timeout option only applies
 * to the default transport).
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/custom_http_client.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

// Any PSR-18 ClientInterface works (Guzzle 7, Symfony HttpClient via
// psr18 adapter, ...). Here: Guzzle with request logging + a proxy slot.
$stack = HandlerStack::create();
$stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
    // Log method + path only. NEVER log the Authorization header.
    error_log(sprintf('[airwallex] %s %s', $request->getMethod(), $request->getUri()->getPath()));

    return $request;
}));

$httpClient = new GuzzleClient([
    'handler' => $stack,
    'timeout' => 30,
    'connect_timeout' => 5,
    // 'proxy' => 'http://corporate-proxy.internal:3128',
]);

$client = new Client(env: Env::Demo, httpClient: $httpClient);

foreach ($client->balances->current() as $balance) {
    printf("%s %s\n", (string) $balance->currency, (string) $balance->available_amount);
}
