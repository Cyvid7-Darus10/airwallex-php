<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Settlement;
use Airwallex\Util;

/**
 * Settlement batches paid out to the account.
 */
final class SettlementsService extends AbstractService
{
    private const BASE = '/api/v1/pa/financial/settlements';

    /**
     * List settlements, optionally filtered by currency/status/settled date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Settlement>
     */
    public function list(
        ?string $currency = null,
        ?string $status = null,
        ?string $fromSettledAt = null,
        ?string $toSettledAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Settlement::class, array_merge([
            'currency' => $currency,
            'status' => $status,
            'from_settled_at' => $fromSettledAt,
            'to_settled_at' => $toSettledAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single settlement by id.
     */
    public function retrieve(string $settlementId): Settlement
    {
        return Settlement::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($settlementId)),
        );
    }
}
