<?php

declare(strict_types=1);

/**
 * Drop-in webhook endpoint: verifies the signature, rejects replays, and
 * routes events. Works with any framework — shown here with plain PHP.
 *
 * Register the endpoint (once) and store the returned secret:
 *
 *     $hook = $client->webhookEndpoints->create(
 *         'https://your-app.example.com/webhooks/airwallex',
 *         ['transfer.settled', 'transfer.failed'],
 *         version: '2022-11-11',
 *     );
 *     // persist $hook->secret (shown only on creation)
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Exception\WebhookSignatureException;
use Airwallex\Webhooks;

$secret = getenv('AIRWALLEX_WEBHOOK_SECRET') ?: '';

// IMPORTANT: verify the RAW body. Parsing and re-encoding the JSON changes
// the bytes and always invalidates the signature.
$payload = (string) file_get_contents('php://input');
$timestamp = $_SERVER['HTTP_X_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

try {
    $event = Webhooks::constructEvent(
        payload: $payload,
        timestamp: $timestamp,
        signature: $signature,
        secret: $secret,
        // toleranceSeconds: 300 (default) — deliveries older than this are
        // rejected as replays; pass null only when replaying stored events.
    );
} catch (WebhookSignatureException $e) {
    http_response_code(400);
    exit;
}

// Airwallex retries deliveries that don't get a 2xx, so respond quickly and
// do heavy work asynchronously (queue a job keyed on $event->id for dedupe).
match ($event->name) {
    'transfer.settled' => error_log('payout settled: ' . json_encode($event->data)),
    'transfer.failed' => error_log('payout FAILED: ' . json_encode($event->data)),
    default => error_log('unhandled event ' . (string) $event->name),
};

http_response_code(200);
