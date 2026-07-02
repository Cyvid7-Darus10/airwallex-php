<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Conversion;
use Airwallex\Util;

/**
 * FX conversions between wallet currencies.
 */
final class ConversionsService extends AbstractService
{
    private const BASE = '/api/v1/conversions';

    /**
     * List FX conversions.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Conversion>
     */
    public function list(
        ?string $status = null,
        ?string $buyCurrency = null,
        ?string $sellCurrency = null,
        ?string $requestId = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Conversion::class, array_merge([
            'status' => $status,
            'buy_currency' => $buyCurrency,
            'sell_currency' => $sellCurrency,
            'request_id' => $requestId,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single conversion by id.
     */
    public function retrieve(string $conversionId): Conversion
    {
        return Conversion::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($conversionId)),
        );
    }

    /**
     * Execute an FX conversion between wallet currencies.
     *
     * A request_id is generated automatically when not supplied so the
     * conversion is idempotent. Specify exactly one of buy_amount or
     * sell_amount:
     *
     *     $client->conversions->create([
     *         'buy_currency' => 'USD',
     *         'sell_currency' => 'SGD',
     *         'buy_amount' => 1000,
     *         'term_agreement' => true,
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Conversion
    {
        return Conversion::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }
}
