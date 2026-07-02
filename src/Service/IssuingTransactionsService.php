<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\IssuingTransaction;
use Airwallex\Util;

/**
 * Cleared card transactions (read-only).
 */
final class IssuingTransactionsService extends AbstractService
{
    private const BASE = '/api/v1/issuing/transactions';

    /**
     * List card transactions, optionally filtered by card/type/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<IssuingTransaction>
     */
    public function list(
        ?string $cardId = null,
        ?string $billingCurrency = null,
        ?string $transactionType = null,
        ?string $digitalWalletTokenId = null,
        ?string $lifecycleId = null,
        ?string $retrievalRef = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, IssuingTransaction::class, array_merge([
            'card_id' => $cardId,
            'billing_currency' => $billingCurrency,
            'transaction_type' => $transactionType,
            'digital_wallet_token_id' => $digitalWalletTokenId,
            'lifecycle_id' => $lifecycleId,
            'retrieval_ref' => $retrievalRef,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single card transaction by id.
     */
    public function retrieve(string $transactionId): IssuingTransaction
    {
        return IssuingTransaction::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($transactionId)),
        );
    }
}
