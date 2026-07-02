<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Page;
use Airwallex\Resource\Customer;
use Airwallex\Resource\CustomerClientSecret;
use Airwallex\Util;

/**
 * Shoppers whose payment details can be saved (/api/v1/pa/customers).
 */
final class CustomersService extends AbstractService
{
    private const BASE = '/api/v1/pa/customers';

    /**
     * List customers, optionally filtered by merchant customer id/date.
     *
     * @param array<string, mixed> $extraParams
     *
     * @return Page<Customer>
     */
    public function list(
        ?string $merchantCustomerId = null,
        ?string $fromCreatedAt = null,
        ?string $toCreatedAt = null,
        int $pageNum = 0,
        ?int $pageSize = null,
        array $extraParams = [],
    ): Page {
        return $this->paged(self::BASE, Customer::class, array_merge([
            'merchant_customer_id' => $merchantCustomerId,
            'from_created_at' => $fromCreatedAt,
            'to_created_at' => $toCreatedAt,
            'page_num' => $pageNum,
            'page_size' => $pageSize,
        ], $extraParams));
    }

    /**
     * Fetch a single customer by id.
     */
    public function retrieve(string $customerId): Customer
    {
        return Customer::make(
            $this->client->get(self::BASE . '/' . Util::encodePathParam($customerId)),
        );
    }

    /**
     * Create a customer.
     *
     * A request_id is generated automatically when not supplied, making the
     * call idempotent:
     *
     *     $client->customers->create([
     *         'merchant_customer_id' => 'cust_42',
     *         'email' => 'jo@example.com',
     *     ]);
     *
     * @param array<string, mixed> $params
     */
    public function create(array $params): Customer
    {
        return Customer::make(
            $this->client->post(self::BASE . '/create', Util::ensureRequestId($params)),
        );
    }

    /**
     * Update an existing customer.
     *
     * @param array<string, mixed> $params
     */
    public function update(string $customerId, array $params): Customer
    {
        return Customer::make($this->client->post(
            self::BASE . '/' . Util::encodePathParam($customerId) . '/update',
            $params,
        ));
    }

    /**
     * Generate a short-lived client secret for customer-scoped calls.
     */
    public function generateClientSecret(string $customerId): CustomerClientSecret
    {
        return CustomerClientSecret::make($this->client->get(
            self::BASE . '/' . Util::encodePathParam($customerId) . '/generate_client_secret',
        ));
    }
}
