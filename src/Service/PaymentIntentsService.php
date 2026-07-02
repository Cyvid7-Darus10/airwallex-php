<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\PaymentIntent;
use Airwallex\Util;

/**
 * Payments collected from shoppers (/api/v1/pa/payment_intents).
 */
final class PaymentIntentsService extends AbstractService
{
    private const BASE = '/api/v1/pa/payment_intents';

    /**
     * List payment intents, optionally filtered by status/currency/order.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<PaymentIntent>
     */
    public function list(
        ?string $status = null,
        ?string $currency = null,
        ?string $merchantOrderId = null,
        ?string $paymentConsentId = null,
        ?string $connectedAccountId = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, PaymentIntent::class, array_merge([
            'status' => $status,
            'currency' => $currency,
            'merchant_order_id' => $merchantOrderId,
            'payment_consent_id' => $paymentConsentId,
            'connected_account_id' => $connectedAccountId,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single payment intent by id.
     */
    public function retrieve(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($paymentIntentId)),
        );
    }

    /**
     * Create a payment intent.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->paymentIntents->create([
     *         'amount' => 25.00,
     *         'currency' => 'USD',
     *         'merchant_order_id' => 'order_42',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): PaymentIntent
    {
        return PaymentIntent::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Confirm a payment intent with a payment method or consent.
     *
     * @param array<string, mixed> $params
     */
    public function confirm(string $paymentIntentId, array $params = []): PaymentIntent
    {
        return PaymentIntent::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($paymentIntentId) . '/confirm',
            $params,
        ));
    }

    /**
     * Continue confirming a payment intent (e.g. after a 3DS challenge).
     *
     * @param array<string, mixed> $params
     */
    public function confirmContinue(string $paymentIntentId, array $params = []): PaymentIntent
    {
        return PaymentIntent::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($paymentIntentId) . '/confirm_continue',
            $params,
        ));
    }

    /**
     * Capture funds authorized by a payment intent.
     *
     * @param array<string, mixed> $params
     */
    public function capture(string $paymentIntentId, array $params = []): PaymentIntent
    {
        return PaymentIntent::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($paymentIntentId) . '/capture',
            $params,
        ));
    }

    /**
     * Cancel a payment intent that has not been captured.
     *
     * @param array<string, mixed> $params
     */
    public function cancel(string $paymentIntentId, array $params = []): PaymentIntent
    {
        return PaymentIntent::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($paymentIntentId) . '/cancel',
            $params,
        ));
    }
}
