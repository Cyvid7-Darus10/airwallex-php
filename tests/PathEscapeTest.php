<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Client;

final class PathEscapeTest extends TestCase
{
    public function testTraversalIdsCannotReachAnotherEndpoint(): void
    {
        $client = $this->client([self::loginResponse(), self::json(200, [])]);

        $client->beneficiaries->retrieve('../create');

        $path = $this->dataRequests()[0]->getUri()->getPath();
        self::assertSame('/api/v1/beneficiaries/..%2Fcreate', $path);
        self::assertStringNotContainsString('../', $path);
    }

    public function testAbsoluteAndQueryInjectionIsNeutralised(): void
    {
        $client = $this->client([self::loginResponse(), self::json(200, [])]);

        $client->transfers->retrieve('tra_1?admin=true#frag');

        $uri = $this->dataRequests()[0]->getUri();
        self::assertSame('/api/v1/transfers/tra_1%3Fadmin%3Dtrue%23frag', $uri->getPath());
        self::assertSame('', $uri->getQuery());
        self::assertSame('', $uri->getFragment());
    }

    public function testEveryPathParameterizedEndpointEscapesItsId(): void
    {
        $evil = 'x/../../y';
        $expect = rawurlencode($evil);

        $calls = [
            fn (Client $client) => $client->transfers->retrieve($evil),
            fn (Client $client) => $client->transfers->cancel($evil),
            fn (Client $client) => $client->batchTransfers->retrieve($evil),
            fn (Client $client) => $client->beneficiaries->update($evil, []),
            fn (Client $client) => $client->beneficiaries->delete($evil),
            fn (Client $client) => $client->payers->delete($evil),
            fn (Client $client) => $client->conversions->retrieve($evil),
            fn (Client $client) => $client->fxQuotes->retrieve($evil),
            fn (Client $client) => $client->globalAccounts->close($evil),
            fn (Client $client) => $client->paymentIntents->confirm($evil),
            fn (Client $client) => $client->customers->generateClientSecret($evil),
            fn (Client $client) => $client->issuingCards->activate($evil),
            fn (Client $client) => $client->issuingCardholders->delete($evil),
            fn (Client $client) => $client->simulation->settleDeposit($evil),
            fn (Client $client) => $client->webhookEndpoints->delete($evil),
        ];

        foreach ($calls as $index => $call) {
            $client = $this->client([self::loginResponse(), self::json(200, [])]);
            $call($client);

            $path = $this->dataRequests()[0]->getUri()->getPath();
            self::assertStringContainsString($expect, $path, "call #{$index}");
            self::assertStringNotContainsString('/../', $path, "call #{$index}");
        }
    }
}
