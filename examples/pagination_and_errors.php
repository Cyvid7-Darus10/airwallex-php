<?php

declare(strict_types=1);

/**
 * Pagination and error-handling patterns.
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/pagination_and_errors.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;
use Airwallex\Exception\ApiException;
use Airwallex\Exception\ConflictException;
use Airwallex\Exception\ConnectionException;
use Airwallex\Exception\NotFoundException;
use Airwallex\Exception\RateLimitException;

$client = new Client(
    env: Env::Demo,
    maxRetries: 2,      // 408/429/5xx and network errors retry automatically
    timeout: 30.0,
);

// --- Auto-pagination: one loop walks every page lazily ----------------------
foreach ($client->transfers->list(pageSize: 100)->autoPagingIterator() as $transfer) {
    printf("%s  %s %s  %s\n", (string) $transfer->short_reference_id, (string) $transfer->transfer_amount, (string) $transfer->transfer_currency, (string) $transfer->status);
}

// --- Manual paging: when you need page boundaries (progress bars, batching) --
$page = $client->beneficiaries->list(pageSize: 20);
printf("page 0: %d beneficiaries, more: %s\n", count($page), $page->hasMore ? 'yes' : 'no');
if ($page->hasMore) {
    $next = $page->nextPage();
    printf("page 1: %d beneficiaries\n", count($next));
}

// --- Typed errors: catch what you can handle, let the rest bubble ------------
try {
    // Well-formed id that doesn't exist -> 404. (A malformed id fails the
    // API's format validation first and raises BadRequestException instead.)
    $client->transfers->retrieve('00000000-0000-4000-8000-000000000000');
} catch (NotFoundException $e) {
    printf("expected 404: %s\n", $e->getMessage());
} catch (ConflictException $e) {
    // 409 = duplicate request_id or invalid state; the SDK NEVER retries
    // these automatically — decide explicitly.
} catch (RateLimitException $e) {
    // 429 that survived the retry budget; back off at the application level.
} catch (ConnectionException $e) {
    // network failure after retries — safe to retry the whole call later,
    // idempotency keys make replays safe for money-moving creates.
} catch (ApiException $e) {
    // everything else; keep the request id for Airwallex support
    printf("api error %d code=%s request_id=%s\n", $e->statusCode, (string) $e->errorCode, (string) $e->requestId);
}

// --- The escape hatch: any endpoint, same auth/retries/errors ----------------
$result = $client->request('GET', '/api/v1/reference/supported_currencies');
printf("supported currency groups: %d\n", is_array($result) ? count($result) : 0);
