<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\ApiClient;
use Airwallex\Client;
use Airwallex\ClientConfig;
use Airwallex\Env;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const CLIENT_ID = 'test_client_id';
    protected const API_KEY = 'test_api_key_5eCr3t';

    protected MockHandler $mock;

    /**
     * @var \ArrayObject<int, array<string, mixed>>
     */
    protected \ArrayObject $history;

    /**
     * @var list<float> delays passed to the retry sleeper
     */
    protected array $sleeps = [];

    /**
     * A Guzzle client backed by a MockHandler queue; every request is
     * recorded into $this->history. Guzzle 7 implements PSR-18, so this
     * doubles as the "injected PSR-18 client" in tests.
     *
     * @param list<Response|\Throwable> $responses
     */
    protected function guzzle(array $responses): GuzzleClient
    {
        $this->history = $container = new \ArrayObject();
        $this->mock = new MockHandler($responses);
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($container));

        return new GuzzleClient(['handler' => $stack]);
    }

    /**
     * @param list<Response|\Throwable> $responses
     */
    protected function apiClient(array $responses, int $maxRetries = 2, ?string $baseUrl = null): ApiClient
    {
        $this->sleeps = [];
        $config = new ClientConfig(
            clientId: self::CLIENT_ID,
            apiKey: self::API_KEY,
            env: Env::Demo,
            baseUrl: $baseUrl,
            maxRetries: $maxRetries,
        );

        return new ApiClient($config, $this->guzzle($responses), function (float $seconds): void {
            $this->sleeps[] = $seconds;
        });
    }

    /**
     * @param list<Response|\Throwable> $responses
     */
    protected function client(array $responses, int $maxRetries = 2, ?string $apiVersion = null): Client
    {
        return new Client(
            clientId: self::CLIENT_ID,
            apiKey: self::API_KEY,
            env: Env::Demo,
            apiVersion: $apiVersion,
            maxRetries: $maxRetries,
            httpClient: $this->guzzle($responses),
        );
    }

    /**
     * A successful login response with a far-future expiry.
     */
    protected static function loginResponse(
        string $token = 'tok_test',
        string $expiresAt = '2999-01-01T00:00:00+0000',
    ): Response {
        return self::json(201, ['token' => $token, 'expires_at' => $expiresAt]);
    }

    /**
     * @param array<string, mixed>|list<mixed> $body
     * @param array<string, string> $headers
     */
    protected static function json(int $status, array $body = [], array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, string> $headers
     */
    protected static function page(array $items, bool $hasMore = false, array $headers = []): Response
    {
        return self::json(200, ['has_more' => $hasMore, 'items' => $items], $headers);
    }

    protected function requestAt(int $index): RequestInterface
    {
        self::assertArrayHasKey($index, $this->history, 'expected at least ' . ($index + 1) . ' requests');
        $request = $this->history[$index]['request'] ?? null;
        self::assertInstanceOf(RequestInterface::class, $request);

        return $request;
    }

    protected function requestCount(): int
    {
        return \count($this->history);
    }

    /**
     * The recorded requests excluding authentication logins.
     *
     * @return list<RequestInterface>
     */
    protected function dataRequests(): array
    {
        $requests = [];
        foreach ($this->history as $entry) {
            $request = $entry['request'] ?? null;
            self::assertInstanceOf(RequestInterface::class, $request);
            if (!str_ends_with($request->getUri()->getPath(), '/authentication/login')) {
                $requests[] = $request;
            }
        }

        return $requests;
    }

    /**
     * @return array<mixed>
     */
    protected static function bodyOf(RequestInterface $request): array
    {
        $decoded = json_decode((string) $request->getBody(), true);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
