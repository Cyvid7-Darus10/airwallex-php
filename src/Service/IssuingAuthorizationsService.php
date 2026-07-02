<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\IssuingAuthorization;
use Airwallex\Util;

/**
 * Pending card authorizations (read-only).
 */
final class IssuingAuthorizationsService extends AbstractService
{
    private const BASE = '/api/v1/issuing/authorizations';

    /**
     * List card authorizations, optionally filtered by card/status/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<IssuingAuthorization>
     */
    public function list(
        ?string $cardId = null,
        ?string $status = null,
        ?string $billingCurrency = null,
        ?string $digitalWalletTokenId = null,
        ?string $lifecycleId = null,
        ?string $retrievalRef = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, IssuingAuthorization::class, array_merge([
            'card_id' => $cardId,
            'status' => $status,
            'billing_currency' => $billingCurrency,
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
     * Fetch a single card authorization by id.
     */
    public function retrieve(string $authorizationId): IssuingAuthorization
    {
        return IssuingAuthorization::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($authorizationId)),
        );
    }
}
