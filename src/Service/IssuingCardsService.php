<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Card;
use Airwallex\Resource\CardLimits;
use Airwallex\Util;

/**
 * Issued corporate cards.
 *
 * The PCI-scoped endpoints (/details and /provision_digital_token) are
 * intentionally not implemented; card numbers returned here are masked.
 */
final class IssuingCardsService extends AbstractService
{
    private const BASE = '/api/v1/issuing/cards';

    /**
     * List cards, optionally filtered by status/cardholder/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Card>
     */
    public function list(
        ?string $cardStatus = null,
        ?string $cardholderId = null,
        ?string $nickName = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        ?string $fromUpdatedAt = null,
        ?string $toUpdatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Card::class, array_merge([
            'card_status' => $cardStatus,
            'cardholder_id' => $cardholderId,
            'nick_name' => $nickName,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'from_updated_at' => $fromUpdatedAt,
            'to_updated_at' => $toUpdatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single card by id.
     */
    public function retrieve(string $cardId): Card
    {
        return Card::make($this->client->get(self::BASE . '/' . Util::encodePathParam($cardId)));
    }

    /**
     * Issue a new card.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->issuingCards->create([
     *         'cardholder_id' => 'chd_123',
     *         'created_by' => 'Jane Doe',
     *         'form_factor' => 'VIRTUAL',
     *         'issue_to' => 'INDIVIDUAL',
     *         'purpose' => 'COMMERCIAL',
     *         'authorization_controls' => [
     *             'allowed_transaction_count' => 'MULTIPLE',
     *         ],
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Card
    {
        return Card::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Update an existing card (status, nickname, controls, ...).
     *
     * @param array<string, mixed> $params
     */
    public function update(string $cardId, array $params): Card
    {
        return Card::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($cardId) . '/update',
            $params,
        ));
    }

    /**
     * Activate a physical card so it can be used.
     */
    public function activate(string $cardId): void
    {
        $this->client->post(self::BASE . '/' . Util::encodePathParam($cardId) . '/activate');
    }

    /**
     * Fetch the card's remaining spend and cash-withdrawal limits.
     */
    public function limits(string $cardId): CardLimits
    {
        return CardLimits::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($cardId) . '/limits'),
        );
    }
}
