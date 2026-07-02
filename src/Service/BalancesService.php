<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Balance;
use Airwallex\Resource\BalanceHistoryItem;

/**
 * Wallet balances.
 */
final class BalancesService extends AbstractService
{
    private const CURRENT_PATH = '/api/v1/balances/current';
    private const HISTORY_PATH = '/api/v1/balances/history';

    /**
     * Retrieve the current balance for every wallet currency.
     *
     * @return list<Balance>
     */
    public function current(): array
    {
        $data = $this->client->get(self::CURRENT_PATH);
        $balances = [];
        foreach (\is_array($data) ? $data : [] as $item) {
            $balances[] = Balance::make($item);
        }

        return $balances;
    }

    /**
     * List ledger movements, newest first.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<BalanceHistoryItem>
     */
    public function history(
        ?string $currency = null,
        ?string $fromPostAt = null,
        ?string $toPostAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::HISTORY_PATH, BalanceHistoryItem::class, array_merge([
            'currency' => $currency,
            'from_post_at' => $fromPostAt,
            'to_post_at' => $toPostAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }
}
