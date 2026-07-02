<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\BatchTransfer;
use Airwallex\Resource\BatchTransferItem;
use Airwallex\Util;

/**
 * Batches of payouts created, quoted and submitted as a unit.
 */
final class BatchTransfersService extends AbstractService
{
    private const BASE = '/api/v1/batch_transfers';

    /**
     * List batch transfers, optionally filtered by status/reference.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<BatchTransfer>
     */
    public function list(
        ?string $status = null,
        ?string $requestId = null,
        ?string $shortReferenceId = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, BatchTransfer::class, array_merge([
            'status' => $status,
            'request_id' => $requestId,
            'short_reference_id' => $shortReferenceId,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single batch transfer by id.
     */
    public function retrieve(string $batchTransferId): BatchTransfer
    {
        return BatchTransfer::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($batchTransferId)),
        );
    }

    /**
     * Create an empty batch to add transfer items to.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent — Airwallex will never execute the same request_id
     * twice, even across the SDK's automatic retries.
     *
     *     $client->batchTransfers->create([
     *         'name' => 'July supplier run',
     *         'transfer_date' => '2026-07-15',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params = []): BatchTransfer
    {
        return BatchTransfer::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Add transfer items (payout drafts) to a batch.
     *
     * @param list<array<string, mixed>> $items
     */
    public function addItems(string $batchTransferId, array $items): BatchTransfer
    {
        return BatchTransfer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($batchTransferId) . '/add_items',
            ['items' => $items],
        ));
    }

    /**
     * Remove transfer items from a batch by item id.
     *
     * @param list<string> $itemIds
     */
    public function deleteItems(string $batchTransferId, array $itemIds): BatchTransfer
    {
        return BatchTransfer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($batchTransferId) . '/delete_items',
            ['item_ids' => $itemIds],
        ));
    }

    /**
     * List the transfer items in a batch.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<BatchTransferItem>
     */
    public function items(
        string $batchTransferId,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(
            self::BASE . '/' . Util::encodePathParam($batchTransferId) . '/items',
            BatchTransferItem::class,
            array_merge(['page_num' => $pageNum, 'page_size' => $pageSize], $extraParams),
        );
    }

    /**
     * Lock FX quotes for every item in the batch.
     *
     * Optionally pass ['validity' => ...] to control how long the quotes hold.
     *
     * @param array<string, mixed> $params
     */
    public function quote(string $batchTransferId, array $params = []): BatchTransfer
    {
        return BatchTransfer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($batchTransferId) . '/quote',
            $params === [] ? null : $params,
        ));
    }

    /**
     * Submit a quoted batch for execution.
     */
    public function submit(string $batchTransferId): BatchTransfer
    {
        return BatchTransfer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($batchTransferId) . '/submit',
        ));
    }

    /**
     * Delete a batch that has not yet been submitted.
     */
    public function delete(string $batchTransferId): BatchTransfer
    {
        return BatchTransfer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($batchTransferId) . '/delete',
        ));
    }
}
