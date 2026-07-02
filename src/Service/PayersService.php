<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Payer;
use Airwallex\Util;

/**
 * Saved payment senders used when creating transfers on behalf of a payer.
 */
final class PayersService extends AbstractService
{
    private const BASE = '/api/v1/payers';

    /**
     * List saved payers, optionally filtered.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Payer>
     */
    public function list(
        ?string $entityType = null,
        ?string $name = null,
        ?string $nickName = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Payer::class, array_merge([
            'entity_type' => $entityType,
            'name' => $name,
            'nick_name' => $nickName,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single payer by id.
     */
    public function retrieve(string $payerId): Payer
    {
        return Payer::make($this->client->get(self::BASE . '/' . Util::encodePathParam($payerId)));
    }

    /**
     * Save a new payer.
     *
     * Pass the payload documented by Airwallex, e.g. ['payer' => [...],
     * 'nickname' => ...]. Use {@see validate()} first to check the details.
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Payer
    {
        return Payer::make($this->client->post(self::BASE . '/create', $params));
    }

    /**
     * Update an existing payer.
     *
     * @param array<string, mixed> $params
     */
    public function update(string $payerId, array $params): Payer
    {
        return Payer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($payerId) . '/update',
            $params,
        ));
    }

    /**
     * Delete a payer.
     */
    public function delete(string $payerId): void
    {
        $this->client->post(self::BASE . '/' . Util::encodePathParam($payerId) . '/delete');
    }

    /**
     * Validate payer details without saving them.
     *
     * Returns the raw validation result.
     *
     * @param array<string, mixed> $params
     */
    public function validate(array $params): mixed
    {
        return $this->client->post(self::BASE . '/validate', $params);
    }
}
