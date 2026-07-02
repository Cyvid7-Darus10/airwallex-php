<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Refund;
use Airwallex\Util;

/**
 * Full or partial refunds of payments (/api/v1/pa/refunds).
 */
final class RefundsService extends AbstractService
{
    private const BASE = '/api/v1/pa/refunds';

    /**
     * List refunds, optionally filtered by status/currency/payment.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Refund>
     */
    public function list(
        ?string $status = null,
        ?string $currency = null,
        ?string $paymentIntentId = null,
        ?string $paymentAttemptId = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Refund::class, array_merge([
            'status' => $status,
            'currency' => $currency,
            'payment_intent_id' => $paymentIntentId,
            'payment_attempt_id' => $paymentAttemptId,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single refund by id.
     */
    public function retrieve(string $refundId): Refund
    {
        return Refund::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($refundId)),
        );
    }

    /**
     * Create a refund.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent:
     *
     *     $client->refunds->create([
     *         'payment_intent_id' => 'int_1',
     *         'amount' => 10.00,
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Refund
    {
        return Refund::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }
}
