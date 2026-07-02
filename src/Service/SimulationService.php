<?php

declare(strict_types=1);

namespace Airwallex\Service;

use Airwallex\Util;

/**
 * Sandbox-only helpers for driving resources through their lifecycle.
 *
 * These endpoints only work in the demo environment (Env::Demo); calling
 * them against production returns an error. Responses are returned as raw
 * decoded JSON since their shapes vary by scenario.
 */
final class SimulationService extends AbstractService
{
    private const BASE = '/api/v1/simulation';

    /**
     * Simulate an incoming deposit to a global account.
     *
     * @param array<string, mixed> $params
     */
    public function createDeposit(array $params): mixed
    {
        return $this->client->post(self::BASE . '/deposit/create', $params);
    }

    /**
     * Settle a simulated deposit.
     */
    public function settleDeposit(string $depositId): mixed
    {
        return $this->client->post(
            self::BASE . '/deposits/' . Util::encodePathParam($depositId) . '/settle',
        );
    }

    /**
     * Reject a simulated deposit.
     */
    public function rejectDeposit(string $depositId): mixed
    {
        return $this->client->post(
            self::BASE . '/deposits/' . Util::encodePathParam($depositId) . '/reject',
        );
    }

    /**
     * Reverse a simulated deposit.
     */
    public function reverseDeposit(string $depositId): mixed
    {
        return $this->client->post(
            self::BASE . '/deposits/' . Util::encodePathParam($depositId) . '/reverse',
        );
    }

    /**
     * Move a transfer to another status, e.g. ['next_status' => 'PAID'].
     *
     * @param array<string, mixed> $params
     */
    public function transitionTransfer(string $transferId, array $params): mixed
    {
        return $this->client->post(
            self::BASE . '/transfers/' . Util::encodePathParam($transferId) . '/transition',
            $params,
        );
    }

    /**
     * Move a legacy payment to another status.
     *
     * @param array<string, mixed> $params
     */
    public function transitionPayment(string $paymentId, array $params): mixed
    {
        return $this->client->post(
            self::BASE . '/payments/' . Util::encodePathParam($paymentId) . '/transition',
            $params,
        );
    }
}
