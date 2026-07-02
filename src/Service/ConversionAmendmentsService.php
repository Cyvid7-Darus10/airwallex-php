<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\ConversionAmendment;
use Airwallex\Resource\ConversionAmendmentQuote;
use Airwallex\Util;

/**
 * Amendments (e.g. cancellations) to existing FX conversions.
 */
final class ConversionAmendmentsService extends AbstractService
{
    private const BASE = '/api/v1/conversion_amendments';

    /**
     * List the amendments applied to a conversion.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<ConversionAmendment>
     */
    public function list(
        string $conversionId,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, ConversionAmendment::class, array_merge([
            'conversion_id' => $conversionId,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single conversion amendment by id.
     */
    public function retrieve(string $conversionAmendmentId): ConversionAmendment
    {
        return ConversionAmendment::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($conversionAmendmentId)),
        );
    }

    /**
     * Execute a conversion amendment (e.g. cancel a conversion).
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->conversionAmendments->create([
     *         'conversion_id' => 'con_123',
     *         'type' => 'CANCELLATION',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): ConversionAmendment
    {
        return ConversionAmendment::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Quote the charges of an amendment without executing it.
     *
     * Takes the same payload as {@see create()}; a request_id is generated
     * automatically when not supplied.
     *
     * @param array<string, mixed> $params
     */
    public function quote(array $params): ConversionAmendmentQuote
    {
        return ConversionAmendmentQuote::make(
            $this->client->post(self::BASE . '/quote', Util::ensureRequestId($params)),
        );
    }
}
