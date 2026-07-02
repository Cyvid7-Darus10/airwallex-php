<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Exception\ConflictException;
use Airwallex\Exception\ConnectionException;
use Airwallex\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

final class RetryTest extends TestCase
{
    public function testA500IsRetriedThenSucceeds(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(500, ['message' => 'boom']),
            self::json(200, ['id' => 'tra_1']),
        ]);

        $result = $api->get('/api/v1/transfers/tra_1');

        self::assertIsArray($result);
        self::assertSame('tra_1', $result['id']);
        self::assertCount(1, $this->sleeps);
    }

    public function testRetriesExhaustIntoServerException(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(500, ['message' => 'boom']),
            self::json(502, ['message' => 'boom']),
            self::json(503, ['message' => 'boom']),
        ], maxRetries: 2);

        try {
            $api->get('/api/v1/account');
            self::fail('expected ServerException');
        } catch (ServerException $exception) {
            self::assertSame(503, $exception->statusCode);
        }

        // 1 login + 3 data attempts (initial + 2 retries).
        self::assertSame(4, $this->requestCount());
        self::assertCount(2, $this->sleeps);
    }

    public function testRetryAfterDeltaSecondsIsHonoured(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(429, ['message' => 'slow down'], ['Retry-After' => '3']),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');

        self::assertSame([3.0], $this->sleeps);
    }

    public function testRetryAfterHttpDateIsHonoured(): void
    {
        $retryAt = gmdate('D, d M Y H:i:s \G\M\T', time() + 10);
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(429, ['message' => 'slow down'], ['Retry-After' => $retryAt]),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');

        self::assertCount(1, $this->sleeps);
        self::assertGreaterThan(7.0, $this->sleeps[0]);
        self::assertLessThanOrEqual(10.5, $this->sleeps[0]);
    }

    public function testAbsurdRetryAfterIsIgnoredInFavourOfBackoff(): void
    {
        // A hostile or misconfigured proxy must not be able to stall the
        // caller: values beyond the cap fall back to jittered backoff.
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(429, ['message' => 'slow down'], ['Retry-After' => '99999999']),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');

        self::assertCount(1, $this->sleeps);
        self::assertLessThanOrEqual(0.5, $this->sleeps[0]);
    }

    public function testRetryAfterInThePastMeansRetryImmediately(): void
    {
        $retryAt = gmdate('D, d M Y H:i:s \G\M\T', time() - 60);
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(429, ['message' => 'slow down'], ['Retry-After' => $retryAt]),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');

        self::assertSame([0.0], $this->sleeps);
    }

    public function testTransportErrorsAreRetried(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            new ConnectException('reset', new Request('GET', '/x')),
            self::json(200, ['ok' => true]),
        ]);

        $result = $api->get('/api/v1/account');

        self::assertIsArray($result);
        self::assertTrue($result['ok']);
    }

    public function testTransportErrorsExhaustIntoConnectionException(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            new ConnectException('reset', new Request('GET', '/x')),
            new ConnectException('reset', new Request('GET', '/x')),
        ], maxRetries: 1);

        try {
            $api->get('/api/v1/account');
            self::fail('expected ConnectionException');
        } catch (ConnectionException $exception) {
            self::assertStringContainsString('after 2 attempt(s)', $exception->getMessage());
        }
    }

    public function testA409IsNeverRetried(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(409, ['code' => 'duplicate_request_id', 'message' => 'already executed']),
        ], maxRetries: 5);

        try {
            $api->post('/api/v1/transfers/create', ['request_id' => 'rid']);
            self::fail('expected ConflictException');
        } catch (ConflictException $exception) {
            self::assertSame('duplicate_request_id', $exception->errorCode);
        }

        // Exactly one login and one data attempt — no retry, no sleep.
        self::assertSame(2, $this->requestCount());
        self::assertSame([], $this->sleeps);
    }

    public function testA408IsRetried(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(408, ['message' => 'timeout']),
            self::json(200, []),
        ]);

        $api->get('/api/v1/account');

        self::assertSame(3, $this->requestCount());
    }

    public function testRetriesReuseTheSameRequestBody(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::json(500, ['message' => 'boom']),
            self::json(201, ['id' => 'tra_1']),
        ]);

        $client->transfers->create(['beneficiary_id' => 'ben_1', 'transfer_amount' => 100]);

        $attempts = $this->dataRequests();
        self::assertCount(2, $attempts);
        $first = self::bodyOf($attempts[0]);
        $second = self::bodyOf($attempts[1]);

        self::assertNotEmpty($first['request_id']);
        self::assertSame($first, $second, 'retry must reuse the identical body, including request_id');
    }

    public function testBackoffDelaysAreJitteredAndCapped(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(500, ['message' => 'boom']),
            self::json(500, ['message' => 'boom']),
            self::json(200, []),
        ], maxRetries: 2);

        $api->get('/api/v1/account');

        self::assertCount(2, $this->sleeps);
        self::assertGreaterThanOrEqual(0.0, $this->sleeps[0]);
        self::assertLessThanOrEqual(0.5, $this->sleeps[0]);
        self::assertGreaterThanOrEqual(0.0, $this->sleeps[1]);
        self::assertLessThanOrEqual(1.0, $this->sleeps[1]);
    }

    public function testZeroMaxRetriesFailsFast(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json(500, ['message' => 'boom']),
        ], maxRetries: 0);

        $this->expectException(ServerException::class);

        $api->get('/api/v1/account');
    }
}
