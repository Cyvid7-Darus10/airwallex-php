<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\FinancialTransaction;
use Airwallex\Util;

/**
 * Ledger entries behind every money movement on the account.
 */
final class FinancialTransactionsService extends AbstractService
{
    private const BASE = '/api/v1/pa/financial/transactions';

    /**
     * List financial transactions, optionally filtered by batch/currency/status/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<FinancialTransaction>
     */
    public function list(
        ?string $batchId = null,
        ?string $currency = null,
        ?string $sourceId = null,
        ?string $status = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, FinancialTransaction::class, array_merge([
            'batch_id' => $batchId,
            'currency' => $currency,
            'source_id' => $sourceId,
            'status' => $status,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single financial transaction by id.
     */
    public function retrieve(string $transactionId): FinancialTransaction
    {
        return FinancialTransaction::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($transactionId)),
        );
    }
}
