<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Cardholder;
use Airwallex\Util;

/**
 * People who can be issued corporate cards.
 */
final class IssuingCardholdersService extends AbstractService
{
    private const BASE = '/api/v1/issuing/cardholders';

    /**
     * List cardholders, optionally filtered by status/email.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Cardholder>
     */
    public function list(
        ?string $cardholderStatus = null,
        ?string $email = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Cardholder::class, array_merge([
            'cardholder_status' => $cardholderStatus,
            'email' => $email,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single cardholder by id.
     */
    public function retrieve(string $cardholderId): Cardholder
    {
        return Cardholder::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($cardholderId)),
        );
    }

    /**
     * Create a cardholder.
     *
     *     $client->issuingCardholders->create([
     *         'email' => 'jane@example.com',
     *         'individual' => [
     *             'name' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
     *             'date_of_birth' => '1990-01-01',
     *         ],
     *         'mobile_number' => '+6591234567',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Cardholder
    {
        return Cardholder::make($this->client->post(self::BASE . '/create', $params));
    }

    /**
     * Update an existing cardholder.
     *
     * @param array<string, mixed> $params
     */
    public function update(string $cardholderId, array $params): Cardholder
    {
        return Cardholder::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($cardholderId) . '/update',
            $params,
        ));
    }

    /**
     * Delete a cardholder that has no active cards.
     *
     * The response carries cardholder_id and a deleted flag.
     */
    public function delete(string $cardholderId): Cardholder
    {
        return Cardholder::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($cardholderId) . '/delete',
        ));
    }
}
