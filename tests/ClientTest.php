<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Client;
use Airwallex\ClientConfig;
use Airwallex\Env;
use Airwallex\Service\AbstractService;
use Airwallex\Service\TransfersService;

final class ClientTest extends TestCase
{
    public function testEveryServiceResolvesOnTheClient(): void
    {
        $client = $this->client([]);
        $names = Client::serviceNames();

        self::assertCount(25, $names);
        foreach ($names as $name) {
            self::assertInstanceOf(AbstractService::class, $client->{$name}, "service {$name}");
            self::assertTrue(isset($client->{$name}));
        }
    }

    public function testServicesAreMemoized(): void
    {
        $client = $this->client([]);

        self::assertSame($client->transfers, $client->transfers);
        self::assertInstanceOf(TransfersService::class, $client->transfers);
    }

    public function testUnknownServiceThrows(): void
    {
        $client = $this->client([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown service "nope"');

        /* @phpstan-ignore-next-line property.notFound */
        $client->nope;
    }

    public function testEnvironmentBaseUrls(): void
    {
        self::assertSame('https://api.airwallex.com', Env::Production->baseUrl());
        self::assertSame('https://api-demo.airwallex.com', Env::Demo->baseUrl());
    }

    public function testRequestsGoToTheEnvironmentHost(): void
    {
        $client = $this->client([self::loginResponse(), self::json(200, ['id' => 'acct_1'])]);
        $client->accounts->retrieve();

        self::assertSame('api-demo.airwallex.com', $this->requestAt(0)->getUri()->getHost());
        self::assertSame('api-demo.airwallex.com', $this->requestAt(1)->getUri()->getHost());
    }

    public function testCredentialsFallBackToEnvironmentVariables(): void
    {
        putenv('AIRWALLEX_CLIENT_ID=env_client');
        putenv('AIRWALLEX_API_KEY=env_key');

        try {
            $client = new Client(httpClient: $this->guzzle([self::loginResponse(), self::json(200, [])]));
            $client->accounts->retrieve();

            self::assertSame('env_client', $this->requestAt(0)->getHeaderLine('x-client-id'));
            self::assertSame('env_key', $this->requestAt(0)->getHeaderLine('x-api-key'));
        } finally {
            putenv('AIRWALLEX_CLIENT_ID');
            putenv('AIRWALLEX_API_KEY');
        }
    }

    public function testCredentialsAreReadFromEnvSuperglobal(): void
    {
        // phpdotenv v5 populates $_ENV without putenv(); the SDK must see it.
        $_ENV['AIRWALLEX_CLIENT_ID'] = 'dotenv_client';
        $_ENV['AIRWALLEX_API_KEY'] = 'dotenv_key';

        try {
            $client = new Client(httpClient: $this->guzzle([self::loginResponse(), self::json(200, [])]));
            $client->accounts->retrieve();

            self::assertSame('dotenv_client', $this->requestAt(0)->getHeaderLine('x-client-id'));
            self::assertSame('dotenv_key', $this->requestAt(0)->getHeaderLine('x-api-key'));
        } finally {
            unset($_ENV['AIRWALLEX_CLIENT_ID'], $_ENV['AIRWALLEX_API_KEY']);
        }
    }

    public function testMissingCredentialThrowsWithoutLeakingAnything(): void
    {
        putenv('AIRWALLEX_CLIENT_ID');
        putenv('AIRWALLEX_API_KEY');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AIRWALLEX_CLIENT_ID');

        new Client();
    }

    public function testHttpBaseUrlIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a https URL');

        new Client(clientId: 'id', apiKey: 'key', baseUrl: 'http://api.evil.example.com');
    }

    public function testHostlessHttpsBaseUrlIsRejected(): void
    {
        // parse_url treats "https:evil.com" as scheme + path with no host;
        // it must fail at construction, not with a confusing runtime error.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('with a host');

        new Client(clientId: 'id', apiKey: 'key', baseUrl: 'https:evil.com');
    }

    public function testHttpLocalhostBaseUrlIsAllowedForTesting(): void
    {
        $client = new Client(
            clientId: 'id',
            apiKey: 'key',
            baseUrl: 'http://localhost:8080',
            httpClient: $this->guzzle([self::loginResponse(), self::json(200, [])]),
        );
        $client->accounts->retrieve();

        self::assertSame('localhost', $this->requestAt(0)->getUri()->getHost());
    }

    public function testNegativeMaxRetriesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client(clientId: 'id', apiKey: 'key', maxRetries: -1);
    }

    public function testVarDumpNeverContainsTheApiKey(): void
    {
        $client = $this->client([]);

        ob_start();
        var_dump($client);
        $dump = (string) ob_get_clean();

        self::assertStringNotContainsString(self::API_KEY, $dump);
        self::assertStringContainsString('[REDACTED]', $dump);
    }

    public function testConfigSerializationRedactsTheApiKey(): void
    {
        $config = new ClientConfig(clientId: 'id', apiKey: 'super_secret', env: Env::Demo);

        self::assertStringNotContainsString('super_secret', serialize($config));

        ob_start();
        var_dump($config);
        $dump = (string) ob_get_clean();
        self::assertStringNotContainsString('super_secret', $dump);
    }

    public function testInjectedPsr18ClientIsUsed(): void
    {
        // guzzle() builds a plain PSR-18 ClientInterface with a mock queue;
        // both the login and the data call must flow through it.
        $client = $this->client([self::loginResponse(), self::json(200, ['id' => 'acct_1'])]);
        $account = $client->accounts->retrieve();

        self::assertSame('acct_1', $account->id);
        self::assertSame(2, $this->requestCount());
        self::assertCount(0, $this->mock, 'mock queue fully consumed');
    }

    public function testEscapeHatchRequestAppliesAuthAndReturnsDecodedJson(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::json(200, ['has_more' => false, 'items' => [['id' => 'disp_1']]]),
        ]);

        $result = $client->request('GET', '/api/v1/pa/payment_disputes', query: ['status' => 'OPEN']);

        self::assertIsArray($result);
        $items = $result['items'] ?? null;
        self::assertIsArray($items);
        $first = $items[0] ?? null;
        self::assertIsArray($first);
        self::assertSame('disp_1', $first['id']);
        $request = $this->requestAt(1);
        self::assertSame('status=OPEN', $request->getUri()->getQuery());
        self::assertSame('Bearer tok_test', $request->getHeaderLine('Authorization'));
    }

    public function testDefaultHeadersArePinned(): void
    {
        $client = new Client(
            clientId: 'id',
            apiKey: 'key',
            env: Env::Demo,
            apiVersion: '2024-08-07',
            onBehalfOf: 'acct_connected',
            httpClient: $this->guzzle([self::loginResponse(), self::json(200, [])]),
        );
        $client->accounts->retrieve();

        $request = $this->requestAt(1);
        self::assertSame('2024-08-07', $request->getHeaderLine('x-api-version'));
        self::assertSame('acct_connected', $request->getHeaderLine('x-on-behalf-of'));
        self::assertSame('airwallex-php/' . Client::VERSION, $request->getHeaderLine('User-Agent'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
    }
}
