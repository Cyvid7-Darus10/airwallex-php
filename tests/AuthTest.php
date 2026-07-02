<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Exception\AuthenticationException;
use Airwallex\Exception\ConnectionException;
use Airwallex\Exception\RateLimitException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

final class AuthTest extends TestCase
{
    public function testLoginSendsCredentialHeadersToTheLoginPath(): void
    {
        $api = $this->apiClient([self::loginResponse(), self::json(200, [])]);
        $api->get('/api/v1/account');

        $login = $this->requestAt(0);
        self::assertSame('POST', $login->getMethod());
        self::assertSame('/api/v1/authentication/login', $login->getUri()->getPath());
        self::assertSame(self::CLIENT_ID, $login->getHeaderLine('x-client-id'));
        self::assertSame(self::API_KEY, $login->getHeaderLine('x-api-key'));
    }

    public function testTokenIsCachedAcrossManyCalls(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(200, []),
            self::json(200, []),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');
        $api->get('/api/v1/account');
        $api->get('/api/v1/account');

        // One login, three data calls.
        self::assertSame(4, $this->requestCount());
        self::assertCount(3, $this->dataRequests());
        foreach ($this->dataRequests() as $request) {
            self::assertSame('Bearer tok_test', $request->getHeaderLine('Authorization'));
        }
    }

    public function testTokenIsRefreshedBeforeExpiry(): void
    {
        // Expires 30s from now: within the 60s refresh leeway, so the second
        // call must log in again.
        $soon = gmdate('Y-m-d\TH:i:s+0000', time() + 30);
        $api = $this->apiClient([
            self::loginResponse('tok_old', $soon),
            self::json(200, []),
            self::loginResponse('tok_new'),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');
        $api->get('/api/v1/account');

        self::assertSame(4, $this->requestCount());
        self::assertSame('Bearer tok_old', $this->requestAt(1)->getHeaderLine('Authorization'));
        self::assertSame('Bearer tok_new', $this->requestAt(3)->getHeaderLine('Authorization'));
    }

    public function testA401TriggersExactlyOneRelogin(): void
    {
        $api = $this->apiClient([
            self::loginResponse('tok_stale'),
            self::json(401, ['code' => 'unauthorized', 'message' => 'token expired']),
            self::loginResponse('tok_fresh'),
            self::json(200, ['id' => 'acct_1']),
        ]);

        $result = $api->get('/api/v1/account');

        self::assertIsArray($result);
        self::assertSame('acct_1', $result['id']);
        self::assertSame('Bearer tok_fresh', $this->requestAt(3)->getHeaderLine('Authorization'));
    }

    public function testASecond401SurfacesAsAuthenticationException(): void
    {
        $api = $this->apiClient([
            self::loginResponse('tok_1'),
            self::json(401, ['code' => 'unauthorized', 'message' => 'nope']),
            self::loginResponse('tok_2'),
            self::json(401, ['code' => 'unauthorized', 'message' => 'still nope']),
        ]);

        $this->expectException(AuthenticationException::class);

        $api->get('/api/v1/account');
    }

    public function testLoginFailureSurfacesTypedError(): void
    {
        $api = $this->apiClient([
            self::json(401, ['code' => 'invalid_api_key', 'message' => 'bad credentials']),
        ]);

        try {
            $api->get('/api/v1/account');
            self::fail('expected AuthenticationException');
        } catch (AuthenticationException $exception) {
            self::assertSame(401, $exception->statusCode);
            self::assertSame('invalid_api_key', $exception->errorCode);
            self::assertStringNotContainsString(self::API_KEY, $exception->getMessage());
        }
    }

    public function testNonJsonLoginBodyIsATypedError(): void
    {
        $api = $this->apiClient([
            new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html'], '<html>proxy says hi</html>'),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('unparseable body');

        $api->get('/api/v1/account');
    }

    public function testLoginWithoutTokenFieldIsATypedError(): void
    {
        $api = $this->apiClient([self::json(201, ['expires_at' => '2999-01-01T00:00:00+0000'])]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('no token');

        $api->get('/api/v1/account');
    }

    public function testLoginGetsTheSameRetryBudgetAsDataEndpoints(): void
    {
        // Two login failures (transport, then 500) exhaust nothing: with
        // maxRetries=2 the third attempt succeeds end-to-end.
        $api = $this->apiClient([
            new ConnectException('dns down', new Request('POST', '/api/v1/authentication/login')),
            self::json(503, ['message' => 'login unavailable']),
            self::loginResponse(),
            self::json(200, ['id' => 'acct_1']),
        ]);

        $result = $api->get('/api/v1/account');

        self::assertIsArray($result);
        self::assertSame('acct_1', $result['id']);
        self::assertCount(2, $this->sleeps);
    }

    public function testLoginTransportErrorsExhaustTheRetryBudget(): void
    {
        $api = $this->apiClient([
            new ConnectException('down', new Request('POST', '/x')),
            new ConnectException('down', new Request('POST', '/x')),
            new ConnectException('down', new Request('POST', '/x')),
        ], maxRetries: 2);

        try {
            $api->get('/api/v1/account');
            self::fail('expected ConnectionException');
        } catch (ConnectionException $exception) {
            self::assertStringContainsString('Login failed after 3 attempt(s)', $exception->getMessage());
            self::assertStringNotContainsString(self::API_KEY, $exception->getMessage());
        }
    }

    public function testLogin429ExhaustsAsRateLimitException(): void
    {
        $api = $this->apiClient([
            self::json(429, ['message' => 'slow down']),
            self::json(429, ['message' => 'slow down']),
        ], maxRetries: 1);

        $this->expectException(RateLimitException::class);

        $api->get('/api/v1/account');
    }

    public function testUnparseableExpiryFallsBackToThirtyMinutes(): void
    {
        $api = $this->apiClient([
            self::loginResponse('tok_1', 'not-a-date'),
            self::json(200, []),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');
        $api->get('/api/v1/account');

        // Fallback TTL keeps the token fresh, so no second login happens.
        self::assertSame(3, $this->requestCount());
    }

    public function testZuluExpiryFormatIsAccepted(): void
    {
        $api = $this->apiClient([
            self::loginResponse('tok_1', '2999-01-01T00:00:00Z'),
            self::json(200, []),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');
        $api->get('/api/v1/account');

        self::assertSame(3, $this->requestCount());
    }
}
