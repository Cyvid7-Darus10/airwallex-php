<?php

declare(strict_types=1);

namespace Airwallex;

use Airwallex\Exception\WebhookSignatureException;

/**
 * Verify and parse incoming Airwallex webhook notifications.
 *
 * Airwallex signs every webhook with your endpoint's secret:
 * `x-signature = HMAC_SHA256(secret, x-timestamp . raw_body)` (hex encoded).
 *
 * Typical usage inside a request handler:
 *
 *     $event = Webhooks::constructEvent(
 *         payload: $request->getContent(),          // raw bytes, NOT re-serialised JSON
 *         timestamp: $request->headers->get('x-timestamp'),
 *         signature: $request->headers->get('x-signature'),
 *         secret: $webhookSecret,
 *     );
 *     if ($event->name === 'transfer.settled') {
 *         // ...
 *     }
 */
final class Webhooks
{
    /**
     * Webhook signatures older than this are rejected to limit replay attacks.
     */
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    private function __construct()
    {
    }

    /**
     * Throw {@see WebhookSignatureException} unless the payload is authentic.
     *
     * Pass the raw request body exactly as received — re-serialising the
     * JSON will change the bytes and invalidate the signature.
     *
     * Set $toleranceSeconds to null to skip the replay-protection timestamp
     * check (useful when replaying stored deliveries in tests).
     *
     * @throws WebhookSignatureException
     */
    public static function verifySignature(
        string $payload,
        string $timestamp,
        string $signature,
        #[\SensitiveParameter]
        string $secret,
        ?int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): void {
        if ($secret === '') {
            throw new \InvalidArgumentException('secret is required to verify webhook signatures');
        }

        if ($toleranceSeconds !== null) {
            if (!is_numeric($timestamp)) {
                throw new WebhookSignatureException(
                    \sprintf('Invalid x-timestamp header: "%s"', $timestamp),
                );
            }
            $sentAt = (float) $timestamp;
            // Airwallex sends unix timestamps in milliseconds.
            if ($sentAt > 1e12) {
                $sentAt /= 1000.0;
            }
            if (abs(microtime(true) - $sentAt) > $toleranceSeconds) {
                throw new WebhookSignatureException(
                    \sprintf(
                        'Webhook timestamp is outside the allowed tolerance of %ds; possible replay',
                        $toleranceSeconds,
                    ),
                );
            }
        }

        $expected = hash_hmac('sha256', $timestamp . $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            throw new WebhookSignatureException('Webhook signature does not match the payload');
        }
    }

    /**
     * Verify the signature and return the parsed {@see WebhookEvent}.
     *
     * @throws WebhookSignatureException
     */
    public static function constructEvent(
        string $payload,
        string $timestamp,
        string $signature,
        #[\SensitiveParameter]
        string $secret,
        ?int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): WebhookEvent {
        self::verifySignature($payload, $timestamp, $signature, $secret, $toleranceSeconds);

        $parsed = json_decode($payload, true);
        if (!\is_array($parsed)) {
            throw new WebhookSignatureException('Webhook payload is not valid JSON');
        }

        return WebhookEvent::make($parsed);
    }
}
