<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Transfer;
use Airwallex\Util;

/**
 * Payouts to beneficiaries.
 *
 * Requires an API version of 2024-01-31 or later (earlier versions call this
 * resource "payments"). Set apiVersion on the client if your account default
 * is older.
 */
final class TransfersService extends AbstractService
{
    private const BASE = '/api/v1/transfers';

    /**
     * List transfers, optionally filtered by status/currency/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Transfer>
     */
    public function list(
        ?string $status = null,
        ?string $currency = null,
        ?string $requestId = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Transfer::class, array_merge([
            'status' => $status,
            'currency' => $currency,
            'request_id' => $requestId,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single transfer by id.
     */
    public function retrieve(string $transferId): Transfer
    {
        return Transfer::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($transferId)),
        );
    }

    /**
     * Create a payout.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->transfers->create([
     *         'beneficiary_id' => 'ben_123',
     *         'source_currency' => 'USD',
     *         'transfer_amount' => 100,
     *         'transfer_currency' => 'PHP',
     *         'transfer_method' => 'LOCAL',
     *         'reference' => 'Invoice 42',
     *         'reason' => 'professional_service_fees',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Transfer
    {
        return Transfer::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Cancel a transfer that has not yet been dispatched.
     */
    public function cancel(string $transferId): Transfer
    {
        return Transfer::make(
            $this->client->post(self::BASE . '/' . Util::encodePathParam($transferId) . '/cancel'),
        );
    }

    /**
     * Validate a transfer payload without creating it.
     *
     * The API requires a request_id in the validation payload (it mirrors the
     * create body); one is generated automatically when not supplied. Returns
     * the raw validation result from Airwallex.
     *
     * @param array<string, mixed> $params
     */
    public function validate(array $params): mixed
    {
        return $this->client->post(self::BASE . '/validate', Util::ensureRequestId($params));
    }

    /**
     * Confirm funding for a transfer that is awaiting funds.
     *
     * Used with transfers created ahead of funding: once the money has
     * arrived (or you choose the funding source), confirming releases the
     * transfer for processing. Pass any funding details documented by
     * Airwallex, e.g. ['funding_source_id' => ...].
     *
     * @param array<string, mixed> $params
     */
    public function confirmFunding(string $transferId, array $params = []): Transfer
    {
        return Transfer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($transferId) . '/confirm_funding',
            $params === [] ? null : $params,
        ));
    }
}
