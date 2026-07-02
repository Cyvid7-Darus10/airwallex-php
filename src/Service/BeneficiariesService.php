<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Beneficiary;
use Airwallex\Util;

/**
 * Saved payout recipients.
 */
final class BeneficiariesService extends AbstractService
{
    private const BASE = '/api/v1/beneficiaries';

    /**
     * List saved beneficiaries, optionally filtered.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Beneficiary>
     */
    public function list(
        ?string $entityType = null,
        ?string $name = null,
        ?string $nickName = null,
        ?string $companyName = null,
        ?string $bankAccountNumber = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Beneficiary::class, array_merge([
            'entity_type' => $entityType,
            'name' => $name,
            'nick_name' => $nickName,
            'company_name' => $companyName,
            'bank_account_number' => $bankAccountNumber,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single beneficiary by id.
     */
    public function retrieve(string $beneficiaryId): Beneficiary
    {
        return Beneficiary::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($beneficiaryId)),
        );
    }

    /**
     * Save a new beneficiary.
     *
     * Pass the payload documented by Airwallex, e.g. ['beneficiary' => [...],
     * 'nickname' => ...]. Use {@see validate()} first to check the details.
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Beneficiary
    {
        return Beneficiary::make($this->client->post(self::BASE . '/create', $params));
    }

    /**
     * Update an existing beneficiary.
     *
     * @param array<string, mixed> $params
     */
    public function update(string $beneficiaryId, array $params): Beneficiary
    {
        return Beneficiary::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($beneficiaryId) . '/update',
            $params,
        ));
    }

    /**
     * Delete a beneficiary.
     */
    public function delete(string $beneficiaryId): void
    {
        $this->client->post(self::BASE . '/' . Util::encodePathParam($beneficiaryId) . '/delete');
    }

    /**
     * Validate beneficiary details without saving them.
     *
     * Returns the raw validation result (shape depends on the payout corridor).
     *
     * @param array<string, mixed> $params
     */
    public function validate(array $params): mixed
    {
        return $this->client->post(self::BASE . '/validate', $params);
    }
}
