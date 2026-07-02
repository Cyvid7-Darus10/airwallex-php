<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\WalletTransfer;
use Airwallex\Util;

/**
 * Transfers between Airwallex wallets.
 */
final class WalletTransfersService extends AbstractService
{
    private const BASE = '/api/v1/wallet_transfers';

    /**
     * List wallet transfers, optionally filtered by status/currency/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<WalletTransfer>
     */
    public function list(
        ?string $status = null,
        ?string $transferCurrency = null,
        ?string $requestId = null,
        ?string $shortReferenceId = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, WalletTransfer::class, array_merge([
            'status' => $status,
            'transfer_currency' => $transferCurrency,
            'request_id' => $requestId,
            'short_reference_id' => $shortReferenceId,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single wallet transfer by id.
     */
    public function retrieve(string $walletTransferId): WalletTransfer
    {
        return WalletTransfer::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($walletTransferId)),
        );
    }

    /**
     * Send funds to another Airwallex wallet.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->walletTransfers->create([
     *         'beneficiary' => ['account_number' => '1234567'],
     *         'transfer_amount' => 100,
     *         'transfer_currency' => 'USD',
     *         'reference' => 'Invoice 42',
     *         'reason' => 'business_expenses',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): WalletTransfer
    {
        return WalletTransfer::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }
}
