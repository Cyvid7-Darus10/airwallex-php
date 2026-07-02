<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Client;

final class IdempotencyTest extends TestCase
{
    private const UUID4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    public function testMoneyMovingCreatesAutoGenerateAUuid4RequestId(): void
    {
        $calls = [
            'transfers' => fn (Client $client) => $client->transfers->create(['beneficiary_id' => 'ben_1']),
            'conversions' => fn (Client $client) => $client->conversions->create(['buy_currency' => 'USD']),
            'fxQuotes' => fn (Client $client) => $client->fxQuotes->create(['buy_currency' => 'USD']),
            'walletTransfers' => fn (Client $client) => $client->walletTransfers->create(['transfer_amount' => 1]),
            'batchTransfers' => fn (Client $client) => $client->batchTransfers->create(['name' => 'run']),
            'conversionAmendments' => fn (Client $client) => $client->conversionAmendments->create(['conversion_id' => 'c']),
            'globalAccounts' => fn (Client $client) => $client->globalAccounts->create(['currency' => 'USD']),
            'paymentIntents' => fn (Client $client) => $client->paymentIntents->create(['amount' => 1]),
            'customers' => fn (Client $client) => $client->customers->create(['email' => 'a@b.c']),
            'refunds' => fn (Client $client) => $client->refunds->create(['payment_intent_id' => 'int_1']),
            'issuingCards' => fn (Client $client) => $client->issuingCards->create(['cardholder_id' => 'chd_1']),
        ];

        foreach ($calls as $name => $call) {
            $client = $this->client([self::loginResponse(), self::json(201, ['id' => 'x'])]);
            $call($client);

            $body = self::bodyOf($this->dataRequests()[0]);
            self::assertArrayHasKey('request_id', $body, "{$name} must send request_id");
            self::assertIsString($body['request_id']);
            self::assertMatchesRegularExpression(self::UUID4_PATTERN, $body['request_id'], $name);
        }
    }

    public function testExplicitRequestIdIsPassedThroughUnchanged(): void
    {
        $client = $this->client([self::loginResponse(), self::json(201, ['id' => 'tra_1'])]);

        $client->transfers->create(['request_id' => 'my-idempotency-key-42', 'beneficiary_id' => 'ben_1']);

        $body = self::bodyOf($this->dataRequests()[0]);
        self::assertSame('my-idempotency-key-42', $body['request_id']);
    }

    public function testEndpointsWithoutRequestIdSupportDoNotInjectOne(): void
    {
        // Spec-verified: beneficiaries, payers, and issuing cardholders do
        // NOT take request_id.
        $calls = [
            'beneficiaries' => fn (Client $client) => $client->beneficiaries->create(['nickname' => 'n']),
            'payers' => fn (Client $client) => $client->payers->create(['nickname' => 'n']),
            'issuingCardholders' => fn (Client $client) => $client->issuingCardholders->create(['email' => 'a@b.c']),
        ];

        foreach ($calls as $name => $call) {
            $client = $this->client([self::loginResponse(), self::json(201, ['id' => 'x'])]);
            $call($client);

            $body = self::bodyOf($this->dataRequests()[0]);
            self::assertArrayNotHasKey('request_id', $body, "{$name} must not invent request_id");
        }
    }

    public function testGeneratedRequestIdsAreUnique(): void
    {
        $ids = [];
        for ($i = 0; $i < 3; ++$i) {
            $client = $this->client([self::loginResponse(), self::json(201, ['id' => 'x'])]);
            $client->transfers->create(['beneficiary_id' => 'ben_1']);
            $body = self::bodyOf($this->dataRequests()[0]);
            self::assertIsString($body['request_id']);
            $ids[] = $body['request_id'];
        }

        self::assertCount(3, array_unique($ids));
    }
}
