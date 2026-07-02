<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Deposit;

/**
 * Deposits received into the wallet.
 */
final class DepositsService extends AbstractService
{
    private const BASE = '/api/v1/deposits';

    /**
     * List deposits received into the wallet.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Deposit>
     */
    public function list(
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Deposit::class, array_merge([
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }
}
