<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\GlobalAccount;
use Airwallex\Resource\GlobalAccountTransaction;
use Airwallex\Util;

/**
 * Local-currency accounts for receiving funds.
 */
final class GlobalAccountsService extends AbstractService
{
    private const BASE = '/api/v1/global_accounts';

    /**
     * List global accounts.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<GlobalAccount>
     */
    public function list(
        ?string $currency = null,
        ?string $countryCode = null,
        ?string $status = null,
        ?string $nickName = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, GlobalAccount::class, array_merge([
            'currency' => $currency,
            'country_code' => $countryCode,
            'status' => $status,
            'nick_name' => $nickName,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single global account by id.
     */
    public function retrieve(string $globalAccountId): GlobalAccount
    {
        return GlobalAccount::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($globalAccountId)),
        );
    }

    /**
     * Open a new global account. request_id is auto-generated if omitted.
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): GlobalAccount
    {
        return GlobalAccount::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Update a global account (e.g. its nickname).
     *
     * @param array<string, mixed> $params
     */
    public function update(string $globalAccountId, array $params): GlobalAccount
    {
        return GlobalAccount::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($globalAccountId) . '/update',
            $params,
        ));
    }

    /**
     * Close a global account.
     */
    public function close(string $globalAccountId): GlobalAccount
    {
        return GlobalAccount::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($globalAccountId) . '/close',
        ));
    }

    /**
     * List transactions received into a global account.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<GlobalAccountTransaction>
     */
    public function transactions(
        string $globalAccountId,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(
            self::BASE . '/' . Util::encodePathParam($globalAccountId) . '/transactions',
            GlobalAccountTransaction::class,
            array_merge([
                'from_created_at' => $fromCreatedAt,
                'to_created_at' => $toCreatedAt,
                'page_num' => $pageNum,
                'page_size' => $pageSize,
            ], $extraParams),
        );
    }
}
